# WhatsApp Notification Environment Control

## Summary

Added environment-based control for WhatsApp notifications to prevent sending messages during testing/development.

## Problem

WhatsApp messages were being sent on every test, which:

-   Wastes API credits during development
-   Sends confusing test messages to real phone numbers
-   Makes testing difficult when you just want to verify other functionality

## Solution

Added `WHATSAPP_ENABLED` environment variable to control whether WhatsApp notifications are sent.

## Changes Made

### 1. Configuration (`config/services.php`)

Added `enabled` flag to WhatsApp service configuration:

```php
'whatsapp' => [
    'enabled' => env('WHATSAPP_ENABLED', false),  // Defaults to false (disabled)
    'api_key' => env('WHATSAPP_API_KEY'),
    'api_url' => env('WHATSAPP_API_URL'),
    'username' => env('WHATSAPP_USERNAME'),
    'source' => env('WHATSAPP_SOURCE', 'booking-system'),
],
```

### 2. Environment Variable (`.env.example`)

Added new environment variable with sensible default:

```bash
# WhatsApp Configuration
WHATSAPP_ENABLED=false  # Set to true in production
WHATSAPP_API_KEY=
WHATSAPP_API_URL=
WHATSAPP_USERNAME=
WHATSAPP_SOURCE=booking-system
```

### 3. Updated All WhatsApp Dispatch Calls

#### Files Modified:

1. **`app/Http/Controllers/PaymentController.php`**

    - Payment success notifications
    - **Added duplicate prevention**: Checks if booking was already completed before sending WhatsApp (prevents duplicate messages on page refresh)

2. **`app/Livewire/Exhibitions/Confirmation.php`**

    - Booking received confirmations

3. **`app/Livewire/Admin/Inquiries/Show.php`**
    - Booking approval with payment link
    - Booking rejection notifications
    - Payment link resend
    - Manual payment confirmation

#### Pattern Applied:

All WhatsApp dispatches now wrapped with environment check:

```php
// Before
SendWhatsAppCampaign::dispatch(...);

// After
if (config('services.whatsapp.enabled')) {
    SendWhatsAppCampaign::dispatch(...);
}
```

#### Duplicate Prevention (Payment Controller):

To prevent duplicate WhatsApp messages when the payment response page is refreshed:

```php
// Track if booking was already completed BEFORE this request
$wasAlreadyCompleted = $booking->status === BookingStatus::PaymentCompleted;

// ... update booking ...

// Only send WhatsApp if this is a NEW completion (not a refresh)
if ($this->ccavenueService->isPaymentSuccessful($responseData)
    && config('services.whatsapp.enabled')
    && !$wasAlreadyCompleted) {
    SendWhatsAppCampaign::dispatch(...);
}
```

## Usage

### Testing/Development (Default)

```bash
# In .env
WHATSAPP_ENABLED=false
```

-   ✅ No WhatsApp messages sent
-   ✅ All other functionality works normally
-   ✅ Logs still show the WhatsApp dispatch was attempted (if needed for debugging)

### Production

```bash
# In .env
WHATSAPP_ENABLED=true
```

-   ✅ WhatsApp messages sent as normal
-   ✅ All notification flows work end-to-end

## Benefits

1. **Clean Testing**: Test payment flows without sending real WhatsApp messages
2. **Cost Control**: Save API credits during development
3. **Safety**: No accidental messages to customers during testing
4. **Flexibility**: Can enable/disable per environment without code changes
5. **Explicit Control**: Developer must explicitly enable in production
6. **Duplicate Prevention**: Payment response page can be refreshed without sending duplicate WhatsApp messages

## Migration Guide

### For Existing Environments

**Local/Development:**

```bash
# Add to .env
WHATSAPP_ENABLED=false
```

**Staging:**

```bash
# Add to .env
WHATSAPP_ENABLED=false  # or true if you want to test WhatsApp flow
```

**Production:**

```bash
# Add to .env
WHATSAPP_ENABLED=true
```

### Verifying It Works

**Test that WhatsApp is disabled:**

1. Set `WHATSAPP_ENABLED=false` in `.env`
2. Complete a payment or booking
3. Verify no WhatsApp messages are sent
4. Check logs confirm other functionality works

**Test that WhatsApp is enabled:**

1. Set `WHATSAPP_ENABLED=true` in `.env`
2. Complete a test booking/payment
3. Verify WhatsApp message is sent

## Affected Notification Types

All WhatsApp notification types now respect the `WHATSAPP_ENABLED` flag:

| Notification Type          | Campaign Name                 | Trigger                            |
| -------------------------- | ----------------------------- | ---------------------------------- |
| Booking Received           | `booking_received`            | Customer submits booking           |
| Booking Approval + Payment | `booking_confirmationpayment` | Admin approves booking             |
| Payment Link Resend        | `booking_confirmationpayment` | Admin resends payment link         |
| Payment Success            | `payment_success`             | Payment completed (auto or manual) |
| Booking Rejection          | `bookingrejected`             | Admin rejects booking              |

## Code Quality

-   ✅ All files formatted with Laravel Pint
-   ✅ No compilation errors
-   ✅ Configuration follows Laravel conventions
-   ✅ Defaults to safe value (false)
-   ✅ Consistent pattern across all dispatch calls
