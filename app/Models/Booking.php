<?php

namespace App\Models;

use App\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'exhibition_id',
        'brand_name',
        'contact_person',
        'phone_code',
        'phone_number',
        'email',
        'city',
        'gst_number',
        'product_profile',
        'has_exhibited_before',
        'participation_years',
        'is_sgcci_member',
        'membership_type',
        'selected_stalls',
        'space_type',
        'total_area',
        'price_per_sqm',
        'total_price',
        'discount_percentage',
        'discount_amount',
        'price_after_discount',
        'gst_amount',
        'total_with_gst',
        'status',
        'is_manual_block',
        'blocked_by',
        'blocked_at',
        'admin_approved_by',
        'admin_approved_at',
        'super_admin_approved_by',
        'super_admin_approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'payment_link',
        'payment_link_sent_at',
        'payment_due_at',
        'payment_completed_at',
        'payment_transaction_id',
        'payment_method',
        'payment_status',
        'payment_amount',
        'payment_response',
        'payment_tracking_id',
        'payment_bank_ref_no',
        'payment_initiated_at',
    ];

    protected $attributes = [
        'status' => 'pending_approval',
        'space_type' => 'standard',
    ];

    protected function casts(): array
    {
        return [
            'product_profile' => 'array',
            'participation_years' => 'array',
            'selected_stalls' => 'array',
            'has_exhibited_before' => 'boolean',
            'is_sgcci_member' => 'boolean',
            'is_manual_block' => 'boolean',
            'total_area' => 'decimal:2',
            'price_per_sqm' => 'decimal:2',
            'total_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'price_after_discount' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_with_gst' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'payment_response' => 'array',
            'status' => BookingStatus::class,
            'admin_approved_at' => 'datetime',
            'super_admin_approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'blocked_at' => 'datetime',
            'payment_link_sent_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'payment_completed_at' => 'datetime',
            'payment_initiated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_code = static::generateUniqueBookingCode();

            // Calculate pricing based on selected stalls and discounts
            $pricing = static::calculatePricing(
                $booking->selected_stalls,
                $booking->has_exhibited_before ?? false,
                $booking->participation_years ?? [],
                $booking->is_sgcci_member ?? false,
                $booking->membership_type,
                $booking->space_type ?? 'standard'
            );

            $booking->total_area = $pricing['total_area'];
            $booking->price_per_sqm = $pricing['price_per_sqm'];
            $booking->total_price = $pricing['total_price'];
            $booking->discount_percentage = $pricing['discount_percentage'];
            $booking->discount_amount = $pricing['discount_amount'];
            $booking->price_after_discount = $pricing['price_after_discount'];
            $booking->gst_amount = $pricing['gst_amount'];
            $booking->total_with_gst = $pricing['total_with_gst'];
        });
    }

    public static function generateUniqueBookingCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        } while (static::where('booking_code', $code)->exists());

        return $code;
    }

    public static function calculateDiscount(
        float $totalArea,
        bool $hasExhibitedBefore = false,
        array $participationYears = [],
        bool $isSgcciMember = false,
        ?string $membershipType = null
    ): float {
        $discounts = [];

        // Loyalty Discount - Based on past participation
        if ($hasExhibitedBefore && count($participationYears) >= 3) {
            $discounts[] = 10; // 10% for 3+ expos
        } elseif ($hasExhibitedBefore && count($participationYears) >= 2) {
            $discounts[] = 5; // 5% for 2 expos
        }

        // Early Bird Discount - Not applicable for now (would need date check)
        // $discounts[] = 10; // 10% early bird

        // SGCCI Members Discount
        if ($isSgcciMember && $membershipType) {
            $membershipDiscounts = [
                'premium-member' => 15,
                'platinum-member' => 12.5,
                'gold-member' => 10,
                'chief-patron' => 5,
                'patron-member' => 5,
            ];

            if (isset($membershipDiscounts[$membershipType])) {
                $discounts[] = $membershipDiscounts[$membershipType];
            }
        }

        // Special Discount based on Booking Area
        if ($totalArea >= 100) {
            $discounts[] = 15; // 15% for 100 sq. mt. & Above
        } elseif ($totalArea >= 60) {
            $discounts[] = 10; // 10% for 60-90 sq. mt.
        }
        // Note: Up to 50 sq. mt. gets N/A (0%)

        // Calculate total discount (sum of all applicable discounts)
        $totalDiscount = array_sum($discounts);

        // Maximum discount allowed is 40%
        return min($totalDiscount, 40);
    }

    public static function calculatePricing(
        array $selectedStalls,
        bool $hasExhibitedBefore = false,
        array $participationYears = [],
        bool $isSgcciMember = false,
        ?string $membershipType = null,
        string $spaceType = 'standard'
    ): array {
        $stallSizes = static::getStallSizes();
        $totalArea = 0;

        // Pricing based on space type
        $pricePerSqm = match ($spaceType) {
            'raw' => 4500,
            'standard' => 5000,
            default => 5000,
        };

        foreach ($selectedStalls as $stallNumber) {
            if (isset($stallSizes[$stallNumber])) {
                $size = $stallSizes[$stallNumber];
                $totalArea += $size['area'];
            } else {
                // Default to 3x3 if stall size not found
                $totalArea += 9; // 3x3 = 9 sq m
            }
        }

        $totalPrice = $totalArea * $pricePerSqm;

        // Calculate discount
        $discountPercentage = static::calculateDiscount(
            $totalArea,
            $hasExhibitedBefore,
            $participationYears,
            $isSgcciMember,
            $membershipType
        );

        $discountAmount = ($totalPrice * $discountPercentage) / 100;
        $priceAfterDiscount = $totalPrice - $discountAmount;

        // GST is calculated on the discounted price
        $gstAmount = $priceAfterDiscount * 0.18; // 18% GST
        $totalWithGst = $priceAfterDiscount + $gstAmount;

        return [
            'total_area' => $totalArea,
            'price_per_sqm' => $pricePerSqm,
            'total_price' => $totalPrice,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'price_after_discount' => $priceAfterDiscount,
            'gst_amount' => $gstAmount,
            'total_with_gst' => $totalWithGst,
        ];
    }

    public static function getStallSizes(): array
    {
        $svgPath = public_path('maps/Main.svg');

        if (! file_exists($svgPath)) {
            return [];
        }

        $svgContent = file_get_contents($svgPath);
        $stallSizes = [];

        // Extract stall numbers and their sizes from SVG
        // Pattern: <g class="stall" data-stall="NUMBER"> ... class="(WIDTH x HEIGHT)"
        preg_match_all('/<g class="stall" data-stall="(\d+)"[^>]*>.*?class="\((\d+) x (\d+)\)"/s', $svgContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $stallNumber = $match[1];
            $width = (int) $match[2];
            $height = (int) $match[3];
            $area = $width * $height;

            $stallSizes[$stallNumber] = [
                'width' => $width,
                'height' => $height,
                'area' => $area,
            ];
        }

        return $stallSizes;
    }

    public static function getStallLineItems(array $selectedStalls, string $spaceType = 'standard'): array
    {
        $stallSizes = static::getStallSizes();

        // Pricing based on space type
        $pricePerSqm = match ($spaceType) {
            'raw' => 4500,
            'standard' => 5000,
            default => 5000,
        };

        $lineItems = [];

        foreach ($selectedStalls as $stallNumber) {
            if (isset($stallSizes[$stallNumber])) {
                $size = $stallSizes[$stallNumber];
                $lineItems[] = [
                    'stall_number' => $stallNumber,
                    'width' => $size['width'],
                    'height' => $size['height'],
                    'area' => $size['area'],
                    'size_display' => $size['width'].' x '.$size['height'],
                    'price' => $size['area'] * $pricePerSqm,
                ];
            } else {
                // Default to 3x3 if stall size not found
                $lineItems[] = [
                    'stall_number' => $stallNumber,
                    'width' => 3,
                    'height' => 3,
                    'area' => 9,
                    'size_display' => '3 x 3',
                    'price' => 9 * $pricePerSqm,
                ];
            }
        }

        return $lineItems;
    }

    /**
     * Check if payment has been completed
     */
    public function isPaymentCompleted(): bool
    {
        return $this->payment_completed_at !== null;
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending(): bool
    {
        return in_array($this->status, [BookingStatus::Allotted, BookingStatus::PaymentPending]) && ! $this->isPaymentCompleted();
    }

    /**
     * Get payment URL for this booking
     */
    public function getPaymentUrl(): string
    {
        return route('payment.initiate', ['bookingCode' => $this->booking_code]);
    }

    public function exhibition(): BelongsTo
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function adminApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function superAdminApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
