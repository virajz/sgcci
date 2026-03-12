<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExhibitorMemberLead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'membership_number',
        'member_type',
        'member_name',
        'member_phone',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
