<?php

namespace App;

enum EntryType: string
{
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Paid => 'Paid',
        };
    }
}
