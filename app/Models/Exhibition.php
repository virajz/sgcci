<?php

namespace App\Models;

use App\EntryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Exhibition extends Model
{
    /** @use HasFactory<\Database\Factories\ExhibitionFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
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
        ];
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
