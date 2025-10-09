# CCAvenue Payment Gateway - Quick Start Guide

## Setup (5 minutes)

### 1. Add Environment Variables

Copy these to your `.env` file and replace with your actual credentials:

```bash
# CCAvenue Payment Gateway Configuration
CCAVENUE_MERCHANT_ID=your_test_merchant_id
CCAVENUE_ACCESS_CODE=your_test_access_code
CCAVENUE_WORKING_KEY=your_test_working_key
CCAVENUE_TEST_MODE=true
CCAVENUE_CURRENCY=INR
CCAVENUE_REDIRECT_URL="${APP_URL}/payment/response"
CCAVENUE_CANCEL_URL="${APP_URL}/payment/cancel"
```

### 2. Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 3. Test the Integration

Create a payment link for any booking:

```
https://your-domain.test/payment/{BOOKING_CODE}
```

## Usage Examples

### In a Blade View

```blade
@if($booking->isPaymentPending())
    <a href="{{ $booking->getPaymentUrl() }}" class="btn btn-primary">
        Pay Now - ₹{{ number_format((float) $booking->total_with_gst, 2) }}
    </a>
@elseif($booking->isPaymentCompleted())
    <span class="badge badge-success">
        Payment Completed
    </span>
@endif
```

### In a Livewire Component

```php
public function proceedToPayment()
{
    if (!$this->booking->isPaymentPending()) {
        $this->addError('payment', 'Payment is not available for this booking.');
        return;
    }

    return redirect()->route('payment.initiate', [
        'bookingCode' => $this->booking->booking_code
    ]);
}
```

### Check Payment Status

```php
// Check if payment is completed
if ($booking->isPaymentCompleted()) {
    // Display receipt or confirmation
}

// Get payment details
$transaction = [
    'id' => $booking->payment_transaction_id,
    'method' => $booking->payment_method,
    'amount' => $booking->payment_amount,
    'status' => $booking->payment_status,
    'completed_at' => $booking->payment_completed_at,
];
```

## Testing

### Quick Test Flow

1. **Create a test booking** (or use existing one)
2. **Get the payment URL**: `https://your-domain.test/payment/BOOK123456`
3. **Visit the URL** - You'll be redirected to CCAvenue
4. **Use test card**: `4111111111111111`
    - CVV: Any 3 digits
    - Expiry: Any future date
5. **Complete payment** on CCAvenue page
6. **Verify redirect** back to your site with success message
7. **Check database** - Booking should have payment details updated

### Verify in Database

```bash
php artisan tinker
```

```php
$booking = Booking::where('booking_code', 'BOOK123456')->first();
$booking->payment_status; // Should be "Success"
$booking->payment_completed_at; // Should have timestamp
$booking->payment_transaction_id; // Should have CCAvenue tracking ID
```

## Common Scenarios

### 1. Add Payment Link to Thank You Page

In `resources/views/livewire/exhibitions/thank-you.blade.php`:

```blade
@if($booking->isPaymentPending())
    <div class="alert alert-info">
        <h4>Complete Your Payment</h4>
        <p>To secure your booking, please complete the payment.</p>
        <a href="{{ $booking->getPaymentUrl() }}" class="btn btn-primary">
            Pay ₹{{ number_format((float) $booking->total_with_gst, 2) }}
        </a>
    </div>
@endif
```

### 2. Send Payment Link via Email

```php
use Illuminate\Support\Facades\Mail;

Mail::to($booking->email)->send(new PaymentReminder($booking));
```

In the email view:

```blade
Please complete your payment using the link below:

{{ $booking->getPaymentUrl() }}

Amount: ₹{{ number_format((float) $booking->total_with_gst, 2) }}
```

### 3. Admin Dashboard - View Payment Status

```php
// In a Livewire component or controller
$pendingPayments = Booking::whereIn('status', [
    BookingStatus::Allotted,
    BookingStatus::PaymentPending
])->whereNull('payment_completed_at')->get();

$completedPayments = Booking::whereNotNull('payment_completed_at')
    ->latest('payment_completed_at')
    ->get();
```

## Troubleshooting Quick Fixes

### Payment not redirecting?

```bash
# Clear routes cache
php artisan route:clear

# Verify routes exist
php artisan route:list | grep payment
```

### Invalid encryption error?

```bash
# Verify working key has no spaces
# Check in .env file - should be single line, no quotes around the key
```

### Response not processing?

```bash
# Check logs
tail -f storage/logs/laravel.log

# Look for "CCAvenue Response Parsed" entries
```

## Need Help?

1. **Check logs**: `storage/logs/laravel.log`
2. **Review documentation**: `docs/ccavenue-payment-integration.md`
3. **Test mode**: Ensure `CCAVENUE_TEST_MODE=true` for testing
4. **Credentials**: Verify all three credentials are from same environment (test/production)

## Production Deployment

When moving to production:

```bash
# 1. Update .env with production credentials
CCAVENUE_MERCHANT_ID=prod_merchant_id
CCAVENUE_ACCESS_CODE=prod_access_code
CCAVENUE_WORKING_KEY=prod_working_key
CCAVENUE_TEST_MODE=false

# 2. Update URLs to use HTTPS
APP_URL=https://your-domain.com
CCAVENUE_REDIRECT_URL="${APP_URL}/payment/response"
CCAVENUE_CANCEL_URL="${APP_URL}/payment/cancel"

# 3. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Test with small amount first!
```

---

**You're all set!** 🎉

Start testing payments at: `https://your-domain.test/payment/{BOOKING_CODE}`
