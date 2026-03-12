<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberScan extends Model
{
    protected $fillable = [
        'membership_number',
        'member_type',
        'member_name',
        'entered_at',
        'exited_at',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
        ];
    }
}
