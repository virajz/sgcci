<?php

namespace App;

enum BookingStatus: string
{
    case PendingApproval = 'pending_approval';
    case ApprovedByAdmin = 'approved_by_admin';
    case Allotted = 'allotted';
    case PaymentPending = 'payment_pending';
    case PaymentCompleted = 'payment_completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            self::ApprovedByAdmin => 'Approved by Admin',
            self::Allotted => 'Allotted',
            self::PaymentPending => 'Payment Pending',
            self::PaymentCompleted => 'Payment Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingApproval => 'yellow',
            self::ApprovedByAdmin => 'blue',
            self::Allotted => 'green',
            self::PaymentPending => 'orange',
            self::PaymentCompleted => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'gray',
            self::Expired => 'red',
            self::Refunded => 'purple',
        };
    }
}
