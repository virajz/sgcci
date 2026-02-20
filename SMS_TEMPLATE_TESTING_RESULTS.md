# SMS Template Testing Results

## Date: 2025-01-04

## Test Summary

Tested 3 different SMS templates on phone number: **7874949091**

### ✅ Successful Tests

All 3 test templates sent successfully:

1. **Booking Confirmation** (DCS=0, route=17)
    - Text: "Dear 11 Your booking for 11 is CONFIRMED. Booking No: 11 Pay by 11: 11 Team SGCCI"
    - Status: ✅ SUCCESS (Code: 000)

2. **OTP Verification** (DCS=0, route=17)
    - Text: "Your OTP to verify your membership is 388003 . - Team SGCCI"
    - Status: ✅ SUCCESS (Code: 000)

3. **Important Notice** (DCS=8, route=4)
    - Text: "IMPORTANT NOTICE Dear Member (12322), SGCCI is transforming into Company..."
    - Status: ✅ SUCCESS (Code: 000)

### ❌ Failed Test

**Visitor Registration Template** (ID: 1707177157041193630)

- Parameters tried: DCS=0/route=17, DCS=8/route=4
- Text formats tried: Normal curly braces, pipe-delimited, without template ID
- Error: `006 - Invalid template text` (all attempts)
- Template text: "Dear {#var#} You are registered for Auto Expo 2025 (8-11 Jan 2025) at Rajpath Club, Ahmedabad. Access your pass: {#var#} Team SGCCI"

## Key Findings

1. **SMS Gateway is Working** - The MyAppStores SMS API is functioning correctly
2. **Both DCS/Route Combinations Work** - DCS=0/route=17 and DCS=8/route=4 both successfully send messages
3. **Template ID 1707177157041193630 is NOT Active** - This specific template fails with "Invalid template text" error while others succeed

## Diagnosis

The visitor registration template (ID: 1707177157041193630) is likely:

- Not approved in the DLT portal, OR
- Not active/enabled for this sender ID, OR
- The registered text doesn't exactly match what we're sending

## Configuration Update

Updated `.env` with working parameters for booking-style messages:

```env
SMS_DCS=0
SMS_ROUTE=17
```

## Next Steps

### 1. Contact SMS Provider

Ask your SMS provider (MyAppStores) to:

- Verify the status of template ID **1707177157041193630**
- Confirm if it's approved and active
- Check if it's associated with sender ID **CHAMBR**
- Get the exact registered template text including variable placeholders

### 2. Alternative: Use a Working Template

If the visitor template cannot be activated quickly, consider:

- Using the booking template format (which works perfectly)
- Creating a new DLT template similar to the booking one
- Requesting approval for a visitor-specific template

### 3. Once Template is Active

When the template is verified as active:

1. Uncomment SMS code in `app/Livewire/Exhibitions/VisitorsRegistration.php` (lines 210-232)
2. Uncomment SMS code in `app/Http/Controllers/VisitorPaymentController.php`
3. Test with a real registration

## Code Status

### Currently Active

- ✅ WhatsApp notifications (working perfectly)
- ✅ Visitor pass image generation
- ✅ CSV export functionality

### Currently Disabled (Awaiting Template Activation)

- ⏳ SMS notifications for visitor registration
- Code exists but is commented out until DLT template is confirmed active

## Test Scripts Created

1. `test-sms.php` - Tests 3 different SMS configurations
2. `test-visitor-template.php` - Specifically tests visitor registration template
3. `app/Console/Commands/TestSmsTemplates.php` - Artisan command for template testing
4. `app/Console/Commands/DebugSmsApi.php` - Debug different DLT parameter formats

## Contact Information

**SMS Provider:** MyAppStores
**API Endpoint:** http://smsl.myappstores.com/api/mt/SendSMS
**Username:** ADMSGCCI
**Sender ID:** CHAMBR
**Template ID:** 1707177157041193630 (needs verification)
