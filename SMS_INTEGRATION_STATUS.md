# SMS Integration Status - SGCCI Visitor Registration

## Current Status: ⚠️ DISABLED

SMS notifications for visitor registration are **temporarily disabled** until the DLT template is approved and configured correctly.

## Issue Summary

The SMS API returns error: **"Invalid template text"** (ErrorCode: 006)

This indicates the DLT (Distributed Ledger Technology) template is either:

1. Not approved yet in the DLT portal
2. Not active/enabled
3. Template text doesn't match what's registered
4. Template registration is pending

## Template Details

**Template ID:** `1707177157041193630`

**Template Text:**

```
Dear {#var#} You are registered for Auto Expo. Access your pass: {#var#} Team SGCCI
```

**Variables:**

1. `{#var#}` - Visitor first name (e.g., "Viraj")
2. `{#var#}` - Pass access link (e.g., "https://stallbooking.sgcci.in/auto-expo/visitors/VIS-ABC123")

**Example:**

```
Dear Viraj You are registered for Auto Expo. Access your pass: https://stallbooking.sgcci.in/auto-expo/visitors/VIS-ABC123 Team SGCCI
```

## SMS Gateway Configuration

**Provider:** MyAppStores SMS API
**Endpoint:** http://smsl.myappstores.com/api/mt/SendSMS

**Current Settings (.env updated):**

- Username: `ADMSGCCI`
- Sender ID: `CHAMBR`
- Channel: `Trans` (Transactional)
- DCS: `8` (Unicode support) ✓ Fixed
- Route: `4` ✓ Fixed

## Next Steps - Action Required

### 1. Verify Template with SMS Provider

Contact MyAppStores support and verify:

- [ ] Is template ID `1707177157041193630` approved in DLT?
- [ ] Is the template status **ACTIVE**?
- [ ] Does the template text match exactly:
    ```
    Dear {#var#} You are registered for Auto Expo. Access your pass: {#var#} Team SGCCI
    ```
- [ ] Are there any additional parameters required (PEID, Entity ID)?
- [ ] What is the correct variable format? (Currently using pipe delimiter: `Name|Link`)

### 2. Test Template

Ask your SMS provider for a **working test API call** with this template, similar to:

```
http://smsl.myappstores.com/api/mt/SendSMS?user=ADMSGCCI&password=Sgcci@25&senderid=CHAMBR&channel=Trans&DCS=8&flashsms=0&number=7874949091&text=[VARIABLE_FORMAT_HERE]&route=4&DLT_TE_ID=1707177157041193630
```

### 3. Enable SMS in Code

Once template is confirmed working, uncomment SMS code in:

**File 1:** `app/Livewire/Exhibitions/VisitorsRegistration.php` (lines ~210-232)

```php
// Remove the // comments from these lines:
// if (config('services.sms.enabled')) {
//     $passLink = route('visitor.scan', [
//         'exhibition' => $exhibition->slug,
//         'registrationCode' => $visitor->registration_code,
//     ]);
//     ...
// }
```

**File 2:** `app/Http/Controllers/VisitorPaymentController.php` (similar section)

### 4. Test Commands Available

Test SMS sending manually:

```bash
# Test with template ID
php artisan sms:test 7874949091 Viraj "https://sgcci.test/test"

# Debug different API formats
php artisan sms:debug 7874949091
```

## Alternative Solution

If DLT template approval is delayed, consider:

**Option 1:** Register a simpler template without the URL:

```
Dear {#var#} You are registered for Auto Expo 2026. Check your email for details. Team SGCCI
```

**Option 2:** Use only WhatsApp notifications (currently working) until SMS template is ready.

## Technical Files Modified

- ✅ `.env` - Updated DCS=8, route=4
- ✅ `app/Services/SmsService.php` - Added `sendWithTemplateId()` method
- ✅ `app/Jobs/SendSmsMessage.php` - Added template ID support
- ⚠️ SMS dispatch code - **Commented out until template is active**
- ✅ Test commands created: `sms:test`, `sms:debug`

## Contact

For DLT template issues, contact:

- **MyAppStores Support** (your SMS provider)
- Reference account: ADMSGCCI
- Sender ID: CHAMBR

## Logs

Check SMS logs for debugging:

```bash
tail -f storage/logs/sms-$(date +%Y-%m-%d).log
```

---

**Last Updated:** February 20, 2026
**Status:** Awaiting DLT template activation
