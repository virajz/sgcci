<?php

namespace App\Console\Commands;

use App\Models\WhatsAppWebhookLog;
use Illuminate\Console\Command;

class PurgeWhatsAppWebhookLogs extends Command
{
    protected $signature = 'app:purge-whatsapp-webhook-logs {--keep=500 : Number of latest entries to keep}';

    protected $description = 'Purge WhatsApp webhook logs, keeping only the latest entries';

    public function handle(): int
    {
        $keep = (int) $this->option('keep');

        $total = WhatsAppWebhookLog::count();

        if ($total <= $keep) {
            $this->info("Nothing to purge. Total entries ({$total}) are within the limit ({$keep}).");

            return self::SUCCESS;
        }

        $cutoffId = WhatsAppWebhookLog::query()
            ->orderByDesc('id')
            ->skip($keep)
            ->value('id');

        $deleted = WhatsAppWebhookLog::where('id', '<=', $cutoffId)->delete();

        $this->info("Purged {$deleted} WhatsApp webhook log entries. Kept the latest {$keep}.");

        return self::SUCCESS;
    }
}
