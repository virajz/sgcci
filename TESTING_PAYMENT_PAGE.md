# Testing the Payment Page - Quick Guide

## The Issue (FIXED ✓)

The payment link was redirecting to the booking page because the status check was wrong:

-   **Before**: Checked for `status === 'approved'` (which doesn't exist)
-   **After**: Checks for `status === Allotted` or `PaymentPending` (correct statuses)

## How to Test the Payment Page

### Step 1: Check Your Booking Status

First, make sure your booking has the correct status:

```bash
php artisan tinker
```

```php
// Find your booking
$booking = Booking::where('booking_code', 'YOUR_BOOKING_CODE')->first();

// Check the status
$booking->status;
// Should be: App\BookingStatus::Allotted or App\BookingStatus::PaymentPending

// If it's not, update it:
$booking->update(['status' => 'allotted']);
// OR
$booking->update(['status' => 'payment_pending']);
```

### Step 2: Access the Payment Page

Visit this URL in your browser:

```
https://sgcci.test/payment/YOUR_BOOKING_CODE
```

Replace `YOUR_BOOKING_CODE` with your actual booking code (e.g., `BOOK123456`)

### Step 3: What You Should See

You should now see the **payment redirect page** with:

-   🔄 Loading spinner
-   Booking details (code, exhibition, amount)
-   Auto-redirect message
-   After 1.5 seconds → redirects to CCAvenue gateway

## Valid Booking Statuses for Payment

Only bookings with these statuses can access the payment page:

-   ✅ `allotted` (BookingStatus::Allotted)
-   ✅ `payment_pending` (BookingStatus::PaymentPending)

If booking has any other status, you'll be redirected to the thank-you page with an error message.

## Quick Test Example

```bash
# In tinker
php artisan tinker

# Get any booking
$booking = Booking::first();

# Set it to allotted status
$booking->update(['status' => 'allotted']);

# Get the payment URL
echo $booking->getPaymentUrl();
# Output: https://sgcci.test/payment/BOOK123456

# Now visit that URL in your browser!
```

## Troubleshooting

### Still Redirecting?

1. **Check the status**:

    ```php
    $booking->status->value; // Should be 'allotted' or 'payment_pending'
    ```

2. **Check if payment already completed**:

    ```php
    $booking->payment_completed_at; // Should be NULL
    ```

3. **Clear cache**:

    ```bash
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    ```

4. **Check logs**:
    ```bash
    tail -f storage/logs/laravel.log
    ```

### Getting 404 Error?

Verify routes exist:

```bash
php artisan route:list --path=payment
```

Should show:

```
GET    payment/{bookingCode}
POST   payment/response
POST   payment/cancel
```

## Working with Admin Panel

If you're using the admin panel to approve bookings, the flow is:

1. Booking created → `pending_approval`
2. Admin approves → `approved_by_admin`
3. Super admin allots → `allotted` ← **NOW CAN PAY**
4. Payment link sent → `payment_pending` ← **CAN STILL PAY**
5. Payment completed → `payment_completed`

## Quick Payment Test Flow

```bash
# 1. Create or find a booking
php artisan tinker
$booking = Booking::first();

# 2. Set to payment-ready status
$booking->update(['status' => 'allotted']);

# 3. Get the payment URL
$url = $booking->getPaymentUrl();
echo $url;

# 4. Visit the URL in browser
# 5. You should see the payment redirect page!
```

---

**The fix is complete!** ✨ Your payment pages should now work correctly.
