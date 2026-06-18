<?php

namespace App\Models;

use App\EntryType;
use Database\Factories\ExhibitionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Exhibition extends Model
{
    /** @use HasFactory<ExhibitionFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'redirect_url',
        'description',
        'logo_path',
        'pass_background_path',
        'pass_qr_x',
        'pass_qr_y',
        'pass_qr_size',
        'pass_name_x',
        'pass_name_y',
        'pass_name_color',
        'invitation_background_path',
        'invitation_stall_x',
        'invitation_stall_y',
        'invitation_company_x',
        'invitation_company_y',
        'invitation_logo_x',
        'invitation_logo_y',
        'invitation_logo_size',
        'invitation_text_color',
        'start_date',
        'end_date',
        'entry_type',
        'entry_amount',
        'registration_closed',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'entry_type' => EntryType::class,
            'entry_amount' => 'decimal:2',
            'registration_closed' => 'boolean',
            'pass_qr_x' => 'integer',
            'pass_qr_y' => 'integer',
            'pass_qr_size' => 'integer',
            'pass_name_x' => 'integer',
            'pass_name_y' => 'integer',
            'invitation_stall_x' => 'integer',
            'invitation_stall_y' => 'integer',
            'invitation_company_x' => 'integer',
            'invitation_company_y' => 'integer',
            'invitation_logo_x' => 'integer',
            'invitation_logo_y' => 'integer',
            'invitation_logo_size' => 'integer',
        ];
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        );
    }

    protected function passBackgroundUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->pass_background_path ? Storage::disk('public')->url($this->pass_background_path) : null,
        );
    }

    protected function invitationBackgroundUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->invitation_background_path ? Storage::disk('public')->url($this->invitation_background_path) : null,
        );
    }

    public function hasCustomPass(): bool
    {
        return $this->pass_background_path !== null
            && $this->pass_qr_x !== null
            && $this->pass_qr_y !== null
            && $this->pass_qr_size !== null;
    }

    public function hasCustomInvitationPass(): bool
    {
        return $this->invitation_background_path !== null
            && $this->invitation_logo_x !== null
            && $this->invitation_logo_y !== null
            && $this->invitation_logo_size !== null;
    }

    public function isPaidEntry(): bool
    {
        return $this->entry_type === EntryType::Paid;
    }

    protected static function booted(): void
    {
        static::creating(function (Exhibition $exhibition) {
            if (empty($exhibition->slug)) {
                $exhibition->slug = static::generateUniqueSlug($exhibition->title);
            }
        });

        static::updating(function (Exhibition $exhibition) {
            if ($exhibition->isDirty('title')) {
                $exhibition->slug = static::generateUniqueSlug($exhibition->title, $exhibition->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(ExhibitionVisitor::class);
    }
}
