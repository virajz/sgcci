# CCAvenue Payment Gateway Integration

This document provides comprehensive documentation for the CCAvenue payment gateway integration in the SGCCI booking system.

## Overview

The CCAvenue payment gateway has been integrated to handle secure online payments for exhibition bookings. The integration uses:

-   **Encryption**: AES-128-CBC encryption for secure data transmission
-   **Test Mode**: Support for both test and production environments
-   **Callback Handling**: Automated response processing and booking updates
-   **WhatsApp Notifications**: Automatic payment confirmation messages

## Files Created/Modified

### Services

-   **`app/Services/CCAvenueService.php`** - Core payment service handling encryption, decryption, and payment data preparation

### Controllers

-   **`app/Http/Controllers/PaymentController.php`** - Handles payment initiation, response callbacks, and cancellation

### Views

-   **`resources/views/payment/redirect.blade.php`** - Payment gateway redirect page
-   **`resources/views/payment/response.blade.php`** - Payment success/failure response page

### Configuration

-   **`config/services.php`** - CCAvenue configuration added
-   **`.env.example`** - Environment variables template added

### Database

-   **Migration**: `2025_10_09_121546_add_payment_fields_to_bookings_table.php`
-   **Model**: Updated `app/Models/Booking.php` with payment fields and helper methods

### Routes

Added to `routes/web.php`:

```php
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/{bookingCode}', [PaymentController::class, 'initiate'])->name('initiate');
    Route::post('/response', [PaymentController::class, 'response'])->name('response');
    Route::post('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
});
```

## Environment Variables

Add these to your `.env` file:

```bash
# CCAvenue Payment Gateway Configuration
CCAVENUE_MERCHANT_ID=your_merchant_id
CCAVENUE_ACCESS_CODE=your_access_code
CCAVENUE_WORKING_KEY=your_working_key
CCAVENUE_TEST_MODE=true
CCAVENUE_CURRENCY=INR
CCAVENUE_REDIRECT_URL="${APP_URL}/payment/response"
CCAVENUE_CANCEL_URL="${APP_URL}/payment/cancel"
```

### Test Credentials

For testing, CCAvenue provides test credentials. Contact CCAvenue support or check your merchant dashboard for:

-   Test Merchant ID
-   Test Access Code
-   Test Working Key

**Important**: Set `CCAVENUE_TEST_MODE=true` for testing. The integration will automatically use the test gateway URL.

## Database Schema

New fields added to `bookings` table:

| Field                    | Type          | Description                                          |
| ------------------------ | ------------- | ---------------------------------------------------- |
| `payment_transaction_id` | string        | CCAvenue tracking ID                                 |
| `payment_method`         | string        | Payment method used (Credit Card, Net Banking, etc.) |
| `payment_status`         | string        | Payment status (Success, Failure, Aborted)           |
| `payment_amount`         | decimal(10,2) | Amount paid                                          |
| `payment_response`       | json          | Complete response from CCAvenue                      |
| `payment_tracking_id`    | string        | CCAvenue tracking ID                                 |
| `payment_bank_ref_no`    | string        | Bank reference number                                |
| `payment_initiated_at`   | timestamp     | When payment was initiated                           |

## Usage

### 1. Initiating a Payment

To redirect a user to payment:

```php
// From a view or component
<a href="{{ route('payment.initiate', ['bookingCode' => $booking->booking_code]) }}">
    Pay Now
</a>

// Or programmatically
return redirect()->route('payment.initiate', ['bookingCode' => $bookingCode]);
```

### 2. Payment Flow

1. **User clicks "Pay Now"** → Redirected to `/payment/{bookingCode}`
2. **PaymentController@initiate** validates booking and generates encrypted request
3. **Redirect page** shows booking details and auto-submits to CCAvenue
4. **User completes payment** on CCAvenue's secure page
5. **CCAvenue redirects back** to `/payment/response` with encrypted response
6. **PaymentController@response** decrypts and processes the response
7. **Booking updated** with payment details
8. **WhatsApp notification** sent (if successful)
9. **User sees result** on response page

### 3. Using in Livewire Components

```php
// In a Livewire component
public function redirectToPayment()
{
    return redirect()->route('payment.initiate', [
        'bookingCode' => $this->booking->booking_code
    ]);
}
```

### 4. Checking Payment Status

```php
// Check if payment is completed
if ($booking->isPaymentCompleted()) {
    // Payment is done
}

// Check if payment is pending
if ($booking->isPaymentPending()) {
    // Show payment link
    $paymentUrl = $booking->getPaymentUrl();
}

// Get payment details
$transactionId = $booking->payment_transaction_id;
$paymentMethod = $booking->payment_method;
$paymentStatus = $booking->payment_status;
```

## API Reference

### CCAvenueService Methods

#### `encrypt(string $plainText): string`

Encrypts data for CCAvenue request using AES-128-CBC.

#### `decrypt(string $encryptedText): string`

Decrypts CCAvenue response data.

#### `preparePaymentData(Booking $booking): array`

Prepares merchant data array for a booking.

#### `generateEncryptedRequest(Booking $booking): string`

Generates complete encrypted request string for CCAvenue.

#### `parseResponse(string $encResponse): array`

Decrypts and parses CCAvenue response into an array.

#### `isPaymentSuccessful(array $responseData): bool`

Checks if payment was successful based on response data.

#### `getGatewayUrl(): string`

Returns appropriate CCAvenue URL (test or production).

#### `getAccessCode(): string`

Returns configured access code.

### Booking Model Helper Methods

#### `isPaymentCompleted(): bool`

Returns `true` if payment has been completed.

#### `isPaymentPending(): bool`

Returns `true` if booking is approved/allotted but payment is not completed.

#### `getPaymentUrl(): string`

Returns the payment initiation URL for this booking.

## Response Data Structure

CCAvenue returns various fields in the response. Common ones include:

```php
[
    'order_id' => 'BOOK123456',           // Your booking code
    'tracking_id' => '123456789012345',   // CCAvenue transaction ID
    'bank_ref_no' => 'ABC123456',         // Bank reference number
    'order_status' => 'Success',          // Success, Failure, Aborted
    'payment_mode' => 'Credit Card',      // Payment method
    'card_name' => 'Visa',                // Card type (if applicable)
    'status_code' => '0',                 // Status code
    'status_message' => 'Success',        // Status message
    'currency' => 'INR',                  // Currency
    'amount' => '67500.00',               // Amount paid
    'billing_name' => 'John Doe',         // Customer name
    'billing_tel' => '919876543210',      // Customer phone
    'billing_email' => 'john@example.com', // Customer email
    'mer_amount' => '67500.00',           // Merchant amount
    'merchant_param1' => '123',           // Booking ID
    'merchant_param2' => '1',             // Exhibition ID
    'merchant_param3' => 'My Brand',      // Brand name
    'trans_date' => '09/10/2025 12:30:45', // Transaction date
]
```

## Testing

### Test Cards (CCAvenue Test Mode)

When in test mode, use these test card numbers:

-   **Success**: 4111111111111111
-   **Failure**: 4000000000000002

**Important**: Only use test cards in test mode. Never use real card numbers for testing.

### Testing Payment Flow

1. Set `CCAVENUE_TEST_MODE=true` in `.env`
2. Configure test credentials from CCAvenue
3. Create a test booking
4. Initiate payment
5. Use test card on CCAvenue page
6. Verify response handling
7. Check database updates
8. Verify WhatsApp notification (if enabled)

## Security Considerations

1. **Never commit credentials** - Keep `.env` file out of version control
2. **Use HTTPS in production** - Configure `APP_URL` with `https://`
3. **Validate responses** - All CCAvenue responses are validated and decrypted
4. **Sanitize inputs** - All user inputs are sanitized before encryption
5. **Log securely** - Payment responses are logged without sensitive card data

## Troubleshooting

### Common Issues

#### 1. "Invalid encryption"

-   Verify `CCAVENUE_WORKING_KEY` is correct
-   Ensure no extra spaces in environment variables

#### 2. "Invalid access code"

-   Check `CCAVENUE_ACCESS_CODE` matches your merchant account
-   Verify you're using correct test/production credentials

#### 3. "Callback not working"

-   Ensure `CCAVENUE_REDIRECT_URL` is publicly accessible
-   Check if URL includes correct protocol (http/https)
-   Verify route exists: `php artisan route:list | grep payment`

#### 4. "Payment successful but booking not updated"

-   Check Laravel logs: `storage/logs/laravel.log`
-   Verify database connection
-   Check response data format

### Debug Mode

Enable detailed logging by checking the Laravel log file:

```bash
tail -f storage/logs/laravel.log
```

Look for entries tagged with:

-   `Payment Initiated`
-   `CCAvenue Response Parsed`
-   `CCAvenue Payment Response`

## Production Checklist

Before going live:

-   [ ] Update environment variables with production credentials
-   [ ] Set `CCAVENUE_TEST_MODE=false`
-   [ ] Set `APP_URL` to production domain with HTTPS
-   [ ] Update `CCAVENUE_REDIRECT_URL` to production URL
-   [ ] Update `CCAVENUE_CANCEL_URL` to production URL
-   [ ] Test complete payment flow with small amount
-   [ ] Verify WhatsApp notifications are working
-   [ ] Set up proper error monitoring
-   [ ] Review and enable production logging
-   [ ] Verify SSL certificate is valid
-   [ ] Test from different devices/networks

## Support

For CCAvenue-specific issues:

-   Visit: https://www.ccavenue.com/
-   Email: service@ccavenue.com
-   Phone: +91-22-6740 4060

For integration issues:

-   Check Laravel logs: `storage/logs/laravel.log`
-   Review this documentation
-   Contact development team

## Additional Resources

-   [CCAvenue API Documentation](https://www.ccavenue.com/merchants_doc.jsp)
-   [CCAvenue Merchant Admin](https://merchant.ccavenue.com/)
-   [Laravel Documentation](https://laravel.com/docs)
