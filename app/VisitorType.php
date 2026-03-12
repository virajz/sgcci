<?php

namespace App;

enum VisitorType: string
{
    case Press = 'press';
    case Vip = 'vip';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::Press => 'Press & Media',
            self::Vip => 'VIP',
            self::Vendor => 'Vendor',
        };
    }

    public function badgeLabel(): string
    {
        return match ($this) {
            self::Press => 'PRESS',
            self::Vip => 'VIP',
            self::Vendor => 'VENDOR',
        };
    }
}
