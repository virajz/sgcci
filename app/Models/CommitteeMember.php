<?php

namespace App\Models;

use Database\Factories\CommitteeMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommitteeMember extends Model
{
    /** @use HasFactory<CommitteeMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'sort_order',
        'membership_number',
        'name',
        'post',
        'post_for_badge',
        'mobile',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
