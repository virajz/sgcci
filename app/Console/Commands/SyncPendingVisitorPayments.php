<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\ExhibitionVisitor;
use App\Services\CCAvenueService;
use App\VisitorRegistrationStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPendingVisitorPayments extends Command
{
    protected $signature = 'visitors:sync-pending-payments
                            {--minutes=10 : Only poll records where payment was initiated at least this many minutes ago}
                            {--limit=10 : Maximum number of records to poll in one run (prevents server overload)}
                            {--expire-hours=24 : Mark as failed if CCAvenue still shows Awaited/Unknown after this many hours}
                            {--dry-run : Print what would change without actually updating}';

    protected $description = 'Poll CCAvenue Order Status API for visitor registrations stuck in payment_pending and resolve them.';

    public function __construct(private readonly CCAvenueService $ccavenueService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $minutesOld = (int) $this->option('minutes');
        $limit = (int) $this->option('limit');
        $expireHours = (int) $this->option('expire-hours');
        $dryRun = (bool) $this->option('dry-run');

        // Only poll records that:
        // 1. Are still payment_pending
        // 2. Had payment_initiated_at set (i.e. the customer was actually redirected to CCAvenue)
        // 3. Were initiated at least $minutesOld minutes ago (give live payments time to complete normally)
        // Oldest-first so the most overdue get resolved first
        $pending = ExhibitionVisitor::query()
            ->where('status', VisitorRegistrationStatus::PaymentPending)
            ->whereNotNull('payment_initiated_at')
            ->where('payment_initiated_at', '<=', now()->subMinutes($minutesOld))
            ->orderBy('payment_initiated_at')
            ->limit($limit)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No stale pending payments found.');

            return self::SUCCESS;
        }

        $this->info("Found {$pending->count()} stale pending payment(s) (limit: {$limit}). Polling CCAvenue...");

        $resolved = 0;
        $skipped = 0;

        foreach ($pending as $visitor) {
            $status = $this->ccavenueService->checkOrderStatus($visitor->registration_code);
            $orderStatus = strtolower($status['order_status'] ?? 'unknown');

            $this->line("  [{$visitor->registration_code}] CCAvenue status: {$status['order_status']}");

            $isExpired = $visitor->payment_initiated_at->lt(now()->subHours($expireHours));

            match (true) {
                $orderStatus === 'success' => $this->resolveSuccess($visitor, $status, $dryRun) ?: $resolved++,
                in_array($orderStatus, ['failure', 'aborted', 'unsuccessful']) => $this->resolveFailure($visitor, $status, $dryRun) ?: $resolved++,
                $isExpired => $this->resolveExpired($visitor, $status, $expireHours, $dryRun) ?: $resolved++,
                default => $skipped++,
            };
        }

        $this->info("Done. Resolved: {$resolved}, Skipped (still unknown): {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Mark visitor as Confirmed and send WhatsApp notification.
     *
     * @param  array<string, mixed>  $statusData
     */
    private function resolveSuccess(ExhibitionVisitor $visitor, array $statusData, bool $dryRun): void
    {
        $this->info("    → Confirming {$visitor->registration_code}");

        if ($dryRun) {
            return;
        }

        $visitor->update([
            'status' => VisitorRegistrationStatus::Confirmed,
            'payment_transaction_id' => $statusData['tracking_id'] ?? null,
            'payment_tracking_id' => $statusData['tracking_id'] ?? null,
            'payment_bank_ref_no' => $statusData['bank_ref_no'] ?? null,
            'payment_method' => $statusData['payment_mode'] ?? 'CCAvenue',
            'payment_status' => $statusData['order_status'] ?? 'Success',
            'payment_response' => $statusData,
            'payment_completed_at' => now(),
            'payment_notes' => 'Resolved via order status poll',
        ]);

        Log::info('SyncPendingVisitorPayments: confirmed via poll', [
            'registration_code' => $visitor->registration_code,
            'tracking_id' => $statusData['tracking_id'] ?? null,
        ]);

        if (config('services.whatsapp.enabled')) {
            $this->sendWhatsAppNotification($visitor->fresh()->load('exhibition'));
        }
    }

    /**
     * Mark visitor as PaymentFailed because CCAvenue still shows Awaited/Unknown after the expiry window.
     *
     * @param  array<string, mixed>  $statusData
     */
    private function resolveExpired(ExhibitionVisitor $visitor, array $statusData, int $expireHours, bool $dryRun): void
    {
        $this->warn("    → Expiring {$visitor->registration_code} (still '{$statusData['order_status']}' after {$expireHours}h)");

        if ($dryRun) {
            return;
        }

        $visitor->update([
            'status' => VisitorRegistrationStatus::PaymentFailed,
            'payment_status' => $statusData['order_status'] ?? 'Awaited',
            'payment_response' => $statusData,
            'payment_notes' => "Expired via order status poll — no payment after {$expireHours}h",
        ]);

        Log::info('SyncPendingVisitorPayments: expired via poll', [
            'registration_code' => $visitor->registration_code,
            'order_status' => $statusData['order_status'] ?? null,
            'expire_hours' => $expireHours,
        ]);
    }

    /**
     * Mark visitor as PaymentFailed.
     *
     * @param  array<string, mixed>  $statusData
     */
    private function resolveFailure(ExhibitionVisitor $visitor, array $statusData, bool $dryRun): void
    {
        $this->warn("    → Marking as failed: {$visitor->registration_code}");

        if ($dryRun) {
            return;
        }

        $visitor->update([
            'status' => VisitorRegistrationStatus::PaymentFailed,
            'payment_status' => $statusData['order_status'] ?? 'Failure',
            'payment_response' => $statusData,
            'payment_notes' => 'Resolved via order status poll',
        ]);

        Log::info('SyncPendingVisitorPayments: marked as failed via poll', [
            'registration_code' => $visitor->registration_code,
            'order_status' => $statusData['order_status'] ?? null,
        ]);
    }

    private function sendWhatsAppNotification(ExhibitionVisitor $visitor): void
    {
        $exhibition = $visitor->exhibition;
        $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');
        $amountPaid = '₹'.number_format((float) $visitor->payment_amount, 2);
        $primaryFirstName = explode(' ', trim($visitor->name))[0];
        $primaryImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $primaryFirstName,
                $exhibition->title,
                $visitor->registration_code,
                $primaryFirstName,
                $visitor->company_name ?: 'N/A',
                $visitor->city,
                $amountPaid,
                $visitor->payment_transaction_id ?: 'N/A',
                $visitor->payment_completed_at->format('d-m-Y'),
                $exhibitionDates,
            ],
            paramsFallbackValue: ['FirstName' => 'Guest'],
            media: [
                'url' => $primaryImageUrl,
                'filename' => 'visitor_pass_'.$visitor->registration_code,
            ]
        );

        foreach ($visitor->additional_persons ?? [] as $index => $person) {
            $personFirstName = explode(' ', trim($person['name']))[0];
            $personImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image?personIndex='.$index;
            $personPhone = ! empty($person['phone_number']) ? $person['phone_number'] : $visitor->phone_number;

            SendWhatsAppCampaign::dispatch(
                campaignName: 'Paidregistration1',
                phoneCode: '',
                phoneNumber: $personPhone,
                templateParams: [
                    $personFirstName,
                    $exhibition->title,
                    $visitor->registration_code,
                    $personFirstName,
                    $visitor->company_name ?: 'N/A',
                    $visitor->city,
                    $amountPaid,
                    $visitor->payment_transaction_id ?: 'N/A',
                    $visitor->payment_completed_at->format('d-m-Y'),
                    $exhibitionDates,
                ],
                paramsFallbackValue: ['FirstName' => 'Guest'],
                media: [
                    'url' => $personImageUrl,
                    'filename' => 'visitor_pass_'.$visitor->registration_code.'_person_'.($index + 1),
                ]
            );
        }
    }
}
