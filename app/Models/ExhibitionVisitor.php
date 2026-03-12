<?php

namespace App\Models;

use App\VisitorRegistrationStatus;
use App\VisitorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'visitor_type',
        'with_invitation_pass',
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
            'with_invitation_pass' => 'boolean',
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
            'status' => VisitorRegistrationStatus::class,
            'visitor_type' => VisitorType::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExhibitionVisitor $visitor) {
            if (empty($visitor->registration_code)) {
                $rawType = $visitor->getRawOriginal('visitor_type') ?? $visitor->getAttributes()['visitor_type'] ?? null;
                $visitorType = $rawType ? VisitorType::tryFrom((string) $rawType) : null;
                $visitor->registration_code = static::generateUniqueCodeForType($visitorType);
            }
        });
    }

    /**
     * Generate a unique registration code for the given visitor type.
     * Special types get a prefixed code (PRESS-, VIP-, VENDOR-), standard visitors get VIS-.
     */
    public static function generateUniqueCodeForType(?VisitorType $type): string
    {
        $prefix = match ($type) {
            VisitorType::Press => 'PRESS-',
            VisitorType::Vip => 'VIP-',
            VisitorType::Vendor => 'VENDOR-',
            default => 'VIS-',
        };

        do {
            $code = $prefix.strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (static::where('registration_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique visitor registration code in VIS-XXXXXX format.
     */
    public static function generateUniqueRegistrationCode(): string
    {
        return static::generateUniqueCodeForType(null);
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

    public function exhibitorLeads(): HasMany
    {
        return $this->hasMany(ExhibitorLead::class);
    }
}
