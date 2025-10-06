<?php

namespace App\Models;

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
        'total_area',
        'price_per_sqm',
        'total_price',
        'gst_amount',
        'total_with_gst',
        'status',
    ];

    protected $attributes = [
        'status' => 'booked',
    ];

    protected function casts(): array
    {
        return [
            'product_profile' => 'array',
            'participation_years' => 'array',
            'selected_stalls' => 'array',
            'has_exhibited_before' => 'boolean',
            'is_sgcci_member' => 'boolean',
            'total_area' => 'decimal:2',
            'price_per_sqm' => 'decimal:2',
            'total_price' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_with_gst' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_code = static::generateUniqueBookingCode();

            // Calculate pricing based on selected stalls
            $pricing = static::calculatePricing($booking->selected_stalls);
            $booking->total_area = $pricing['total_area'];
            $booking->price_per_sqm = $pricing['price_per_sqm'];
            $booking->total_price = $pricing['total_price'];
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

    public static function calculatePricing(array $selectedStalls): array
    {
        $stallSizes = static::getStallSizes();
        $totalArea = 0;
        $pricePerSqm = 750; // ₹750 per square meter for 3x3 stalls

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
        $gstAmount = $totalPrice * 0.18; // 18% GST
        $totalWithGst = $totalPrice + $gstAmount;

        return [
            'total_area' => $totalArea,
            'price_per_sqm' => $pricePerSqm,
            'total_price' => $totalPrice,
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

    public static function getStallLineItems(array $selectedStalls): array
    {
        $stallSizes = static::getStallSizes();
        $pricePerSqm = 750;
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

    public function exhibition(): BelongsTo
    {
        return $this->belongsTo(Exhibition::class);
    }
}
