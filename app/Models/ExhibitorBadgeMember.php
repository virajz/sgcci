<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExhibitorBadgeMember extends Model
{
    /** @use HasFactory<\Database\Factories\ExhibitorBadgeMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'name',
        'phone_number',
        'photo',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
