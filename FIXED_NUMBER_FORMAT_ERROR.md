# Fixed: TypeError with number_format()

## Issue

```
TypeError - Internal Server Error
number_format(): Argument #1 ($num) must be of type int|float, string given
```

**Location**: `app/Services/CCAvenueService.php:64`

## Root Cause

In Laravel, when you cast a model attribute to `decimal:2`, it returns a **string** representation, not a float. This is because decimal casting preserves exact precision for financial calculations.

In the Booking model:

```php
'total_with_gst' => 'decimal:2',  // Returns string like "67500.00"
```

When `number_format()` was called with this string value in PHP 8.4, it threw a TypeError because it now strictly requires int|float.

## Solution

Cast the decimal string to float before passing to `number_format()`:

```php
// Before (ERROR)
number_format($booking->total_with_gst, 2, '.', '')

// After (FIXED)
number_format((float) $booking->total_with_gst, 2, '.', '')
```

## Files Fixed

1. **`app/Services/CCAvenueService.php`** (Line 64)

    - Fixed in `preparePaymentData()` method

2. **`app/Http/Controllers/PaymentController.php`** (Line 111)

    - Fixed in WhatsApp notification formatting

3. **`resources/views/payment/redirect.blade.php`** (Line 106)

    - Fixed in amount display

4. **`resources/views/payment/response.blade.php`** (Line 152)
    - Fixed in amount display

## Why This Happened

PHP 8.0+ enforces strict type checking for built-in functions. In earlier PHP versions, `number_format()` would silently convert strings to numbers. In PHP 8.4, this is now a TypeError.

## Testing

Try the payment page again:

```
https://sgcci.test/payment/47DT3WP8
```

The error should now be resolved and you should see the payment redirect page! ✅

## Prevention

When working with decimal/money fields in Laravel:

-   Always cast to `(float)` before using `number_format()`
-   Or use Laravel's `Number` helper (Laravel 11+): `Number::currency($amount, 'INR')`
-   Or create a Blade directive/component for consistent formatting

## Additional Note

This is a common issue when upgrading to PHP 8.x with Laravel applications that handle financial data. The `decimal` cast is correct for database storage (preserves precision), but requires explicit type conversion for functions that expect numeric types.
