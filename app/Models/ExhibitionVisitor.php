<?php

namespace App\Models;

use App\VisitorRegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExhibitionVisitor extends Model
{
    /** @use HasFactory<\Database\Factories\ExhibitionVisitorFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_code',
        'exhibition_id',
        'invited_by_booking_id',
        'phone_number',
        'name',
        'company_name',
        'designation',
        'state',
        'city',
        'email',
        'business_segment',
        'sub_business_segment',
        'additional_persons',
        'source',
        'status',
        'payment_amount',
        'payment_transaction_id',
        'payment_tracking_id',
        'payment_bank_ref_no',
        'payment_method',
        'payment_status',
        'payment_response',
        'payment_initiated_at',
        'payment_completed_at',
        'payment_notes',
        'entered_at',
        'exited_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'payment_amount' => 'decimal:2',
            'additional_persons' => 'array',
            'payment_response' => 'array',
            'payment_initiated_at' => 'datetime',
            'payment_completed_at' => 'datetime',
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
            'status' => VisitorRegistrationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExhibitionVisitor $visitor) {
            if (empty($visitor->registration_code)) {
                $visitor->registration_code = static::generateUniqueRegistrationCode();
            }
        });
    }

    /**
     * Generate a unique visitor registration code in VIS-XXXXXX format.
     */
    public static function generateUniqueRegistrationCode(): string
    {
        do {
            $code = 'VIS-'.strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (static::where('registration_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique invited guest registration code in INVIS-XXXXXX format.
     */
    public static function generateUniqueInvitedGuestCode(): string
    {
        do {
            $code = 'INVIS-'.strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (static::where('registration_code', $code)->exists());

        return $code;
    }

    public function exhibition(): BelongsTo
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function invitedByBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'invited_by_booking_id');
    }
}
