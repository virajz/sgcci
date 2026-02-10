<?php

namespace App;

enum VisitorRegistrationStatus: string
{
    case Pending = 'pending';
    case PaymentPending = 'payment_pending';
    case Confirmed = 'confirmed';
    case PaymentFailed = 'payment_failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::PaymentPending => 'Payment Pending',
            self::Confirmed => 'Confirmed',
            self::PaymentFailed => 'Payment Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::PaymentPending => 'orange',
            self::Confirmed => 'green',
            self::PaymentFailed => 'red',
            self::Cancelled => 'gray',
        };
    }
}
