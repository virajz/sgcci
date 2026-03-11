<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Illuminate\Console\Command;

class SendVisitorEventReminderNotifications extends Command
{
    protected $signature = 'visitors:send-event-reminders
                            {--phone= : Only send to this phone number}
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send WhatsApp event reminder notifications to confirmed visitors for upcoming exhibitions';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $phoneFilter = $this->option('phone');

        $upcomingExhibitions = Exhibition::query()
            ->whereBetween('start_date', [now()->addDay()->startOfDay(), now()->addDays(3)->endOfDay()])
            ->get();

        if ($upcomingExhibitions->isEmpty()) {
            $this->warn('No upcoming exhibitions found.');

            return self::SUCCESS;
        }

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($upcomingExhibitions as $exhibition) {
            $daysUntilEvent = (int) now()->startOfDay()->diffInDays($exhibition->start_date->startOfDay());

            $visitors = ExhibitionVisitor::query()
                ->where('exhibition_id', $exhibition->id)
                ->where('status', VisitorRegistrationStatus::Confirmed)
                ->when($phoneFilter, fn($q) => $q->where('phone_number', $phoneFilter))
                ->get();

            if ($visitors->isEmpty()) {
                continue;
            }

            $this->info("Exhibition: {$exhibition->title} (in {$daysUntilEvent} day(s)) — {$visitors->count()} visitor(s)");

            if ($isDryRun) {
                $this->warn('DRY RUN MODE - No messages will be sent');
            }

            $progressBar = $this->output->createProgressBar($visitors->count());
            $progressBar->start();

            foreach ($visitors as $visitor) {
                if (! $isDryRun) {
                    try {
                        $this->sendReminderNotification($visitor, $exhibition, $daysUntilEvent);
                        $totalSent++;
                    } catch (\Exception $e) {
                        $totalFailed++;
                        $this->newLine();
                        $this->error("Failed for {$visitor->name}: {$e->getMessage()}");
                    }
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        }

        if ($isDryRun) {
            $this->info('Dry run complete. No messages were sent.');
        } else {
            $this->info("Done. Sent: {$totalSent}, Failed: {$totalFailed}");
        }

        return self::SUCCESS;
    }

    private function sendReminderNotification(ExhibitionVisitor $visitor, Exhibition $exhibition, int $daysUntilEvent): void
    {
        $firstName = explode(' ', trim($visitor->name))[0] ?: 'Guest';
        $exhibitionDates = $exhibition->start_date->format('d M Y') . ' to ' . $exhibition->end_date->format('d M Y');

        $imageUrl = config('app.url') . '/' . $exhibition->slug . '/visitor-pass/' . $visitor->registration_code . '/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'autoremainder',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $firstName,
                (string) $daysUntilEvent,
                $exhibition->title,
                $visitor->registration_code,
                $exhibitionDates,
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $imageUrl,
                'filename' => 'visitor_pass_' . $visitor->registration_code,
            ],
        );
    }
}
