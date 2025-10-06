<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'exhibition_id',
        'brand_name',
        'contact_person',
        'phone_code',
        'phone_number',
        'email',
        'city',
        'product_profile',
        'has_exhibited_before',
        'participation_years',
        'is_sgcci_member',
        'membership_type',
        'selected_stalls',
    ];

    protected function casts(): array
    {
        return [
            'product_profile' => 'array',
            'participation_years' => 'array',
            'selected_stalls' => 'array',
            'has_exhibited_before' => 'boolean',
            'is_sgcci_member' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_code = static::generateUniqueBookingCode();
        });
    }

    public static function generateUniqueBookingCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        } while (static::where('booking_code', $code)->exists());

        return $code;
    }

    public function exhibition(): BelongsTo
    {
        return $this->belongsTo(Exhibition::class);
    }
}
