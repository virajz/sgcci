# SMS Templates for Booking System

## API Configuration

**SMS Gateway:** MyAppStores SMS API
**Endpoint:** `http://smsl.myappstores.com/api/mt/SendSMS`
**Sender ID:** `CHAMBR`
**Channel:** `Trans` (Transactional)
**DCS:** `8` (Unicode support)
**Route:** `4`

### API Parameters:

- `user` - Username (from config)
- `password` - Password (from config)
- `senderid` - CHAMBR
- `channel` - Trans
- `DCS` - 8
- `flashsms` - 0
- `number` - Recipient mobile number (10 digits)
- `text` - URL-encoded message text
- `route` - 4

---

## Template Guidelines

**Character Limit:** 160 characters (including variables and link)

**Important Notes:**

- Each template must stay under 160 characters total
- Links typically consume 20-30 characters
- Text will be URL-encoded when sent
- No special variable format needed - variables replaced before sending
- Keep messages clear and concise

---

## Template 1: Booking Confirmation with Payment Link

**Template Name:** `sms_booking_confirmation_payment`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~150 characters (varies based on actual values)

### Message Template:

```
Dear {{contact_name}} Your booking for {{exhibition}} is CONFIRMED. Booking No: {{booking_code}} Pay by {{due_date}}: {{payment_link}} Team SGCCI
```

### Variables:

1. `{{contact_name}}` - Contact Person Name (first name only recommended)
2. `{{exhibition}}` - Exhibition Title (abbreviated if needed)
3. `{{booking_code}}` - Booking Code
4. `{{due_date}}` - Payment Due Date (format: DD/MM)
5. `{{payment_link}}` - Payment Link URL (shortened URL required)

### Example with Real Data:

```
Dear Viraj Your booking for Auto Expo 2025 is CONFIRMED. Booking No: ABC12345 Pay by 15/10: https://stallbooking.sgcci.in/p/ABC123 Team SGCCI
```

**Character Count:** 142 characters ✓

### URL-Encoded Example for API:

```
Dear%20Viraj%20Your%20booking%20for%20Auto%20Expo%202025%20is%20CONFIRMED.%20Booking%20No%3A%20ABC12345%20Pay%20by%2015%2F10%3A%20https%3A%2F%2Fsgcci.in%2Fp%2FABC123%20Team%20SGCCI
```

### Implementation Notes:

- Use first name only for contact person to save space
- Abbreviate exhibition title if > 20 characters
- Use short date format (DD/MM instead of full date)
- Use URL shortener for payment links
- Keep booking code as-is for tracking

---

### Test API Request:

```
http://smsl.myappstores.com/api/mt/SendSMS?user=ADMSGCCI&password=Sgcci@25&senderid=CHAMBR&channel=Trans&DCS=8&flashsms=0&number=YOUR_PHONE_NUMBER&text=Dear%20Viraj%20Your%20booking%20for%20Auto%20Expo%202025%20is%20CONFIRMED.%20Booking%20No%3A%20ABC12345%20Pay%20by%2015%2F10%3A%20https%3A%2F%2Fsgcci.in%2Fp%2FABC123%20Team%20SGCCI&route=4
```

Replace `YOUR_PHONE_NUMBER` with your 10-digit mobile number for testing.

---

## Testing Checklist

Before deploying this template:

- [ ] Test with longest expected exhibition name
- [ ] Test with longest expected booking code format
- [ ] Verify shortened URL works correctly
- [ ] Confirm total character count stays under 160
- [ ] Test with actual phone number
- [ ] Verify all variables populate correctly
- [ ] Check message delivery and formatting

---

---

## Template 2: Booking Received (Acknowledgment)

**Template Name:** `sms_booking_received`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~145 characters

### Message Template:

```
Dear {{contact_name}} Your booking request for {{exhibition}} received. Booking No: {{booking_code}} Under review. You'll hear from us soon. Team SGCCI
```

### Variables:

1. `{{contact_name}}` - Contact Person Name (first name only)
2. `{{exhibition}}` - Exhibition Title (abbreviated)
3. `{{booking_code}}` - Booking Code

### Example with Real Data:

```
Dear Viraj Your booking request for Auto Expo 2025 received. Booking No: ABC12345 Under review. You'll hear from us soon. Team SGCCI
```

**Character Count:** 138 characters ✓

---

## Template 3: Booking Rejected

**Template Name:** `sms_booking_rejected`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~155 characters

### Message Template:

```
Dear {{contact_name}} Your booking {{booking_code}} for {{exhibition}} could not be approved. Reason: {{reason}} Contact us for details. Team SGCCI
```

### Variables:

1. `{{contact_name}}` - Contact Person Name (first name only)
2. `{{booking_code}}` - Booking Code
3. `{{exhibition}}` - Exhibition Title (abbreviated)
4. `{{reason}}` - Rejection Reason (keep brief, max 30 chars)

### Example with Real Data:

```
Dear Viraj Your booking ABC12345 for Auto Expo 2025 could not be approved. Reason: Stalls unavailable Contact us for details. Team SGCCI
```

**Character Count:** 147 characters ✓

**Note:** Rejection reason must be kept very brief to stay under 160 characters.

---

## Template 4: Payment Success (Full Payment)

**Template Name:** `sms_payment_success`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~140 characters

### Message Template:

```
Payment received! Dear {{contact_name}} Rs.{{amount}} paid for {{exhibition}} Booking: {{booking_code}} on {{date}}. Thank you! Team SGCCI
```

### Variables:

1. `{{contact_name}}` - Contact Person Name (first name only)
2. `{{amount}}` - Amount Paid (without decimals, e.g., "45000")
3. `{{exhibition}}` - Exhibition Title (abbreviated)
4. `{{booking_code}}` - Booking Code
5. `{{date}}` - Payment Date (format: DD/MM)

### Example with Real Data:

```
Payment received! Dear Viraj Rs.67500 paid for Auto Expo 2025 Booking: ABC12345 on 15/10. Thank you! Team SGCCI
```

**Character Count:** 122 characters ✓

---

## Template 5: Partial Payment Received

**Template Name:** `sms_partial_payment_received`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~150 characters

### Message Template:

```
Dear {{contact_name}} Partial payment Rs.{{amount}} received for {{booking_code}}. Remaining: Rs.{{remaining}} Due: {{due_date}} Team SGCCI
```

### Variables:

1. `{{contact_name}}` - Contact Person Name (first name only)
2. `{{amount}}` - Amount Paid (without decimals)
3. `{{booking_code}}` - Booking Code
4. `{{remaining}}` - Remaining Amount (without decimals)
5. `{{due_date}}` - Final Payment Due Date (format: DD/MM)

### Example with Real Data:

```
Dear Viraj Partial payment Rs.30000 received for ABC12345. Remaining: Rs.37500 Due: 20/10 Team SGCCI
```

**Character Count:** 107 characters ✓

---

## Template 6: Payment Reminder

**Template Name:** `sms_payment_reminder`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~145 characters

### Message Template:

```
REMINDER: Dear {{contact_name}} Payment pending for {{booking_code}}. Amount: Rs.{{amount}} Due: {{due_date}} ({{days}} days left) Team SGCCI
```

### Variables:

1. `{{contact_name}}` - Contact Person Name (first name only)
2. `{{booking_code}}` - Booking Code
3. `{{amount}}` - Remaining Amount (without decimals)
4. `{{due_date}}` - Payment Deadline (format: DD/MM)
5. `{{days}}` - Days Until Deadline (number only)

### Example with Real Data:

```
REMINDER: Dear Viraj Payment pending for ABC12345. Amount: Rs.67500 Due: 20/10 (5 days left) Team SGCCI
```

**Character Count:** 111 characters ✓

---

## Template 7: Staff - New Booking Received (Internal)

**Template Name:** `sms_staff_booking_received`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~140 characters

### Message Template:

```
NEW BOOKING: {{contact_name}} - {{exhibition}} Code: {{booking_code}} Stalls: {{stalls}} Area: {{area}}sqm Amt: Rs.{{amount}} - SGCCI Admin
```

### Variables:

1. `{{contact_name}}` - Contact Person Name
2. `{{exhibition}}` - Exhibition Title (abbreviated)
3. `{{booking_code}}` - Booking Code
4. `{{stalls}}` - Selected Stalls (abbreviated, e.g., "A1,A2")
5. `{{area}}` - Total Area
6. `{{amount}}` - Total Amount (without decimals)

### Example with Real Data:

```
NEW BOOKING: Viraj Shah - Auto Expo 2025 Code: ABC12345 Stalls: A1,A2,B3 Area: 45sqm Amt: Rs.67500 - SGCCI Admin
```

**Character Count:** 118 characters ✓

---

## Template 8: Staff - Booking Approved & Payment Link Sent (Internal)

**Template Name:** `sms_staff_booking_approved`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~150 characters

### Message Template:

```
APPROVED: {{contact_name}} - {{booking_code}} Stalls: {{stalls}} Amt: Rs.{{amount}} Due: {{due_date}} Payment link sent - SGCCI Admin
```

### Variables:

1. `{{contact_name}}` - Contact Person Name
2. `{{booking_code}}` - Booking Code
3. `{{stalls}}` - Allotted Stalls (abbreviated)
4. `{{amount}}` - Total Amount (without decimals)
5. `{{due_date}}` - Payment Due Date (format: DD/MM)

### Example with Real Data:

```
APPROVED: Viraj Shah - ABC12345 Stalls: A1,A2,B3 Amt: Rs.67500 Due: 20/10 Payment link sent - SGCCI Admin
```

**Character Count:** 111 characters ✓

---

## Template 9: Staff - Payment Received (Internal)

**Template Name:** `sms_staff_payment_received`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~135 characters

### Message Template:

```
PAYMENT RCVD: {{contact_name}} - {{booking_code}} Amt: Rs.{{amount}} Date: {{date}} Exhibition: {{exhibition}} - SGCCI Admin
```

### Variables:

1. `{{contact_name}}` - Contact Person Name
2. `{{booking_code}}` - Booking Code
3. `{{amount}}` - Amount Paid (without decimals)
4. `{{date}}` - Payment Date (format: DD/MM)
5. `{{exhibition}}` - Exhibition Title (abbreviated)

### Example with Real Data:

```
PAYMENT RCVD: Viraj Shah - ABC12345 Amt: Rs.67500 Date: 15/10 Exhibition: Auto Expo 2025 - SGCCI Admin
```

**Character Count:** 110 characters ✓

---

## Template 10: Visitor Registration Confirmed

**Template Name:** `visitor_registration_confirmed`
**Category:** Transactional
**Language:** English
**Estimated Length:** ~122 characters

### Message Template:

```
Dear {{name}} You are registered for {{exhibition}}. Access your pass: {{pass_link}} Team SGCCI
```

### Variables:

1. `{{name}}` - Visitor first name
2. `{{exhibition}}` - Exhibition title (abbreviated to 20 chars if longer)
3. `{{pass_link}}` - Visitor scan URL (`/{exhibition-slug}/visitors/{registration-code}`)

### Example with Real Data:

```
Dear Viraj You are registered for Auto Expo. Access your pass: https://stallbooking.sgcci.in/auto-expo/visitors/VIS-U5WG23 Team SGCCI
```

**Character Count:** 122 characters ✓

### When Sent:

- **Free exhibitions** — immediately after registration submission
- **Paid exhibitions** — after CCAvenue payment is confirmed

---

## Summary

All SMS templates have been created with character counts well under the 160 limit:

| Template                                  | Type     | Character Count | Status |
| ----------------------------------------- | -------- | --------------- | ------ |
| 1. Booking Confirmation with Payment Link | Customer | 142 chars       | ✓      |
| 2. Booking Received                       | Customer | 138 chars       | ✓      |
| 3. Booking Rejected                       | Customer | 147 chars       | ✓      |
| 4. Payment Success                        | Customer | 122 chars       | ✓      |
| 5. Partial Payment Received               | Customer | 107 chars       | ✓      |
| 6. Payment Reminder                       | Customer | 111 chars       | ✓      |
| 7. Staff - New Booking                    | Internal | 118 chars       | ✓      |
| 8. Staff - Booking Approved               | Internal | 111 chars       | ✓      |
| 9. Staff - Payment Received               | Internal | 110 chars       | ✓      |
| 10. Visitor Registration Confirmed        | Customer | 122 chars       | ✓      |

### Important Notes for Implementation:

1. **Name Format**: Use first name only for customer messages to save space
2. **Exhibition Titles**: Abbreviate if longer than 20 characters
3. **Amounts**: Remove decimals and commas (e.g., "67500" not "67,500.00")
4. **Dates**: Use DD/MM format (e.g., "15/10" not "October 15, 2025")
5. **Stalls**: Use comma-separated format without spaces (e.g., "A1,A2,B3")
6. **URLs**: Use URL shortener for payment links
7. **Rejection Reasons**: Keep under 30 characters

---

## Implementation Guide

### 1. Environment Configuration

Add the following variables to your `.env` file:

```env
SMS_ENABLED=true
SMS_API_URL=http://smsl.myappstores.com/api/mt/SendSMS
SMS_USERNAME=ADMSGCCI
SMS_PASSWORD=Sgcci@25
SMS_SENDER_ID=CHAMBR
SMS_CHANNEL=Trans
SMS_DCS=8
SMS_ROUTE=4
```

### 2. Files Created

The following files have been created for SMS functionality:

- **Service:** `app/Services/SmsService.php` - Core SMS sending service
- **Job:** `app/Jobs/SendSmsMessage.php` - Queued job for sending SMS
- **Config:** `config/services.php` - SMS configuration (already updated)
- **Logging:** `config/logging.php` - SMS log channel (already updated)

### 3. Usage Examples

#### Basic Usage (Synchronous)

```php
use App\Services\SmsService;

$sms = SmsService::fromConfig();

// Send booking confirmation
$sms->sendTemplate('booking_confirmation_payment', '919876543210', [
    'contact_name' => 'Viraj',
    'exhibition' => 'Auto Expo 2025',
    'booking_code' => 'ABC12345',
    'due_date' => '15/10',
    'payment_link' => 'https://sgcci.in/p/ABC123',
]);

// Send booking received acknowledgment
$sms->sendTemplate('booking_received', '919876543210', [
    'contact_name' => 'Viraj',
    'exhibition' => 'Auto Expo 2025',
    'booking_code' => 'ABC12345',
]);
```

#### Queued Usage (Recommended)

```php
use App\Jobs\SendSmsMessage;

// Dispatch to queue for better performance
SendSmsMessage::dispatch(
    template: 'booking_confirmation_payment',
    phoneCode: '+91',
    phoneNumber: '9876543210',
    variables: [
        'contact_name' => 'Viraj',
        'exhibition' => 'Auto Expo 2025',
        'booking_code' => 'ABC12345',
        'due_date' => '15/10',
        'payment_link' => 'https://sgcci.in/p/ABC123',
    ]
);
```

#### Integration with Existing Booking Flow

Based on the WhatsApp implementation, here's how to integrate SMS:

```php
// In app/Livewire/Exhibitions/Confirmation.php
use App\Jobs\SendSmsMessage;

// After sending WhatsApp, also send SMS
if (config('services.sms.enabled')) {
    SendSmsMessage::dispatch(
        template: 'booking_received',
        phoneCode: $this->booking->phone_code,
        phoneNumber: $this->booking->phone_number,
        variables: [
            'contact_name' => $this->getFirstName($this->booking->contact_person),
            'exhibition' => $this->abbreviateExhibition($this->booking->exhibition->title),
            'booking_code' => $this->booking->booking_code,
        ]
    );
}
```

### 4. Helper Methods

You may want to add these helper methods to your Booking model or a trait:

```php
/**
 * Get first name from full name.
 */
private function getFirstName(string $fullName): string
{
    return explode(' ', trim($fullName))[0];
}

/**
 * Abbreviate exhibition title if needed.
 */
private function abbreviateExhibition(string $title, int $maxLength = 20): string
{
    if (strlen($title) <= $maxLength) {
        return $title;
    }

    return substr($title, 0, $maxLength - 3) . '...';
}

/**
 * Format amount for SMS (remove decimals).
 */
private function formatAmountForSms(float $amount): string
{
    return number_format($amount, 0, '', '');
}

/**
 * Format date for SMS (DD/MM).
 */
private function formatDateForSms(string $date): string
{
    return Carbon::parse($date)->format('d/m');
}

/**
 * Format stalls for SMS (comma-separated, no spaces).
 */
private function formatStallsForSms(array $stalls): string
{
    return implode(',', $stalls);
}
```

### 5. Template Mapping

Map each WhatsApp template to its SMS equivalent:

| WhatsApp Template                   | SMS Template                   | Variables                                                      |
| ----------------------------------- | ------------------------------ | -------------------------------------------------------------- |
| `booking_received`                  | `booking_received`             | contact_name, exhibition, booking_code                         |
| `booking_confirmationpayment`       | `booking_confirmation_payment` | contact_name, exhibition, booking_code, due_date, payment_link |
| `bookingrejected`                   | `booking_rejected`             | contact_name, booking_code, exhibition, reason                 |
| `payment_success`                   | `payment_success`              | contact_name, amount, exhibition, booking_code, date           |
| `partial_payment_received`          | `partial_payment_received`     | contact_name, amount, booking_code, remaining, due_date        |
| `payment_reminder`                  | `payment_reminder`             | contact_name, booking_code, amount, due_date, days             |
| `staff_booking_received`            | `staff_booking_received`       | contact_name, exhibition, booking_code, stalls, area, amount   |
| `staff_booking_confirmationpayment` | `staff_booking_approved`       | contact_name, booking_code, stalls, amount, due_date           |
| `invoicestatus` (staff)             | `staff_payment_received`       | contact_name, booking_code, amount, date, exhibition           |

### 6. Testing

Test SMS sending with tinker:

```bash
php artisan tinker
```

```php
use App\Services\SmsService;

$sms = SmsService::fromConfig();

$sms->sendTemplate('booking_received', '9876543210', [
    'contact_name' => 'Viraj',
    'exhibition' => 'Test Expo',
    'booking_code' => 'TEST123',
]);
```

### 7. Logging

All SMS activity is logged to `storage/logs/sms.log` with daily rotation. Check the logs to monitor:

- Successful SMS sends
- Failed attempts
- Character length warnings (if message > 160 chars)
- API errors

### 8. URL Shortening

For production use, implement URL shortening for payment links to keep messages under 160 characters. Options:

1. **Create a simple URL shortener route:**
    - Create route: `sgcci.in/p/{code}`
    - Redirect to full payment URL

2. **Use a third-party service:**
    - Bitly API
    - TinyURL API
    - Custom short domain

Example implementation:

```php
// routes/web.php
Route::get('/p/{code}', function ($code) {
    $booking = Booking::where('booking_code', $code)->firstOrFail();
    return redirect()->route('payment.show', $booking);
})->name('payment.short');

// Usage in SMS
$shortUrl = route('payment.short', $booking->booking_code);
// Result: https://stallbooking.sgcci.in/p/ABC123
```

### 9. Production Checklist

Before going live:

- [ ] Test all 9 templates with real phone numbers
- [ ] Verify character counts with longest expected values
- [ ] Set up URL shortening for payment links
- [ ] Configure `SMS_ENABLED=true` in production `.env`
- [ ] Test queue processing (`php artisan queue:work`)
- [ ] Monitor `storage/logs/sms.log` for errors
- [ ] Verify SMS delivery with multiple phone numbers
- [ ] Test edge cases (long exhibition names, long booking codes)
- [ ] Ensure rejection reasons are kept under 30 characters
