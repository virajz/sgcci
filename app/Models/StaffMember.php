<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    /** @use HasFactory<\Database\Factories\StaffMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_code',
        'phone_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the full phone number with country code
     */
    public function getFullPhoneNumberAttribute(): string
    {
        return $this->phone_code.$this->phone_number;
    }

    /**
     * Scope to get only active staff members
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
