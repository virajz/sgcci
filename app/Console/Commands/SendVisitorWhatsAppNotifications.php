<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Illuminate\Console\Command;

class SendVisitorWhatsAppNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitors:send-whatsapp
                            {--exhibition= : Exhibition ID or slug to filter visitors}
                            {--dry-run : Show what would be sent without actually sending}
                            {--status=confirmed : Visitor status to filter (confirmed, payment_pending, payment_failed)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp notifications to registered visitors';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $exhibitionFilter = $this->option('exhibition');
        $statusFilter = $this->option('status');

        // Build query
        $query = ExhibitionVisitor::with('exhibition');

        // Filter by status
        $status = match (strtolower($statusFilter)) {
            'confirmed' => VisitorRegistrationStatus::Confirmed,
            'payment_pending' => VisitorRegistrationStatus::PaymentPending,
            'payment_failed' => VisitorRegistrationStatus::PaymentFailed,
            default => VisitorRegistrationStatus::Confirmed,
        };
        $query->where('status', $status);

        // Filter by exhibition
        if ($exhibitionFilter) {
            $exhibition = Exhibition::where('id', $exhibitionFilter)
                ->orWhere('slug', $exhibitionFilter)
                ->first();

            if (! $exhibition) {
                $this->error("Exhibition not found: {$exhibitionFilter}");

                return self::FAILURE;
            }

            $query->where('exhibition_id', $exhibition->id);
            $this->info("Filtering by exhibition: {$exhibition->title}");
        }

        $visitors = $query->get();

        if ($visitors->isEmpty()) {
            $this->warn('No visitors found matching the criteria.');

            return self::SUCCESS;
        }

        $this->info("Found {$visitors->count()} visitor(s)");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No messages will be sent');
            $this->newLine();
        }

        $progressBar = $this->output->createProgressBar($visitors->count());
        $progressBar->start();

        $sent = 0;
        $failed = 0;

        foreach ($visitors as $visitor) {
            $exhibition = $visitor->exhibition;

            if ($isDryRun) {
                // Show what would be sent
                $totalMessages = 1 + count($visitor->additional_persons ?? []);
                $this->newLine(2);
                $this->info("Would send {$totalMessages} message(s) to {$visitor->phone_number} for {$visitor->name}");
            } else {
                try {
                    $this->sendWhatsAppNotifications($visitor, $exhibition);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed for {$visitor->name}: {$e->getMessage()}");
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->info("Dry run complete. Would have processed {$visitors->count()} visitor(s)");
        } else {
            $this->info("Sent: {$sent}, Failed: {$failed}");
        }

        return self::SUCCESS;
    }

    private function sendWhatsAppNotifications(ExhibitionVisitor $visitor, Exhibition $exhibition): void
    {
        $exhibitionDates = $exhibition->start_date->format('d M Y') . ' to ' . $exhibition->end_date->format('d M Y');

        // Determine payment info based on status
        if ($visitor->status === VisitorRegistrationStatus::Confirmed && $visitor->payment_amount) {
            $amountPaid = '₹' . number_format((float) $visitor->payment_amount, 2);
            $transactionId = $visitor->payment_transaction_id ?: 'N/A';
            $paymentDate = $visitor->payment_completed_at ? $visitor->payment_completed_at->format('d-m-Y') : 'N/A';
        } else {
            $amountPaid = 'Free Entry';
            $transactionId = 'N/A';
            $paymentDate = $visitor->created_at->format('d-m-Y');
        }

        // Send WhatsApp for primary visitor
        $primaryFirstName = explode(' ', trim($visitor->name))[0];
        $primaryImageUrl = config('app.url') . '/' . $exhibition->slug . '/visitor-pass/' . $visitor->registration_code . '/image';

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
                $transactionId,
                $paymentDate,
                $exhibitionDates,
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $primaryImageUrl,
                'filename' => 'visitor_pass_' . $visitor->registration_code,
            ]
        );

        // Send WhatsApp for each additional person
        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personFirstName = explode(' ', trim($person['name']))[0];
                $personImageUrl = config('app.url') . '/' . $exhibition->slug . '/visitor-pass/' . $visitor->registration_code . '/image?personIndex=' . $index;

                SendWhatsAppCampaign::dispatch(
                    campaignName: 'Paidregistration1',
                    phoneCode: '',
                    phoneNumber: $visitor->phone_number,
                    templateParams: [
                        $personFirstName,
                        $exhibition->title,
                        $visitor->registration_code,
                        $personFirstName,
                        $visitor->company_name ?: 'N/A',
                        $visitor->city,
                        $amountPaid,
                        $transactionId,
                        $paymentDate,
                        $exhibitionDates,
                    ],
                    paramsFallbackValue: [
                        'FirstName' => 'Guest',
                    ],
                    media: [
                        'url' => $personImageUrl,
                        'filename' => 'visitor_pass_' . $visitor->registration_code . '_person_' . ($index + 1),
                    ]
                );
            }
        }
    }
}
