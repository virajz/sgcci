<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookLog extends Model
{
    /** @use HasFactory<\Database\Factories\WhatsAppWebhookLogFactory> */
    use HasFactory;

    protected $table = 'whatsapp_webhook_logs';

    protected $fillable = [
        'method',
        'path',
        'headers',
        'payload',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
        ];
    }
}
