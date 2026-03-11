<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppInquiry extends Model
{
    /** @use HasFactory<\Database\Factories\WhatsAppInquiryFactory> */
    use HasFactory;

    protected $table = 'whatsapp_inquiries';

    protected $fillable = [
        'booking_id',
        'phone_number',
        'name',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
