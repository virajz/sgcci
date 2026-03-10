<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExhibitorLead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'exhibition_visitor_id',
        'person_index',
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

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(ExhibitionVisitor::class, 'exhibition_visitor_id');
    }
}
