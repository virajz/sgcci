# WhatsApp Business Templates for Booking System

## Template 1: Booking Received (Acknowledgment)

**Template Name:** `booking_received`
**Category:** UTILITY
**Language:** English

### Message:

```
Hello {{1}},

Thank you for your booking inquiry for *{{2}}*!

We have received your request for the following stalls:
{{3}}

*Booking Details:*
📋 Booking Code: *{{4}}*
📍 Total Area: {{5}} sq m
💰 Amount: ₹{{6}}

Your booking is currently under review by our admin team. You will receive a confirmation message once it has been processed.

For any queries, please contact us or reply to this message.

Thank you,
*SGCCI Team*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Selected Stalls (comma-separated list)
4. `{{4}}` - Booking Code
5. `{{5}}` - Total Area
6. `{{6}}` - Total Amount with GST

---

## Template 2: Booking Confirmation with Payment Link

**Template Name:** `booking_confirmation_payment`
**Category:** UTILITY
**Language:** English

### Message:

```
🎉 Congratulations {{1}}!

Your booking for *{{2}}* has been *CONFIRMED*!

*Allotted Stalls:*
{{3}}

*Booking Summary:*
📋 Booking Code: *{{4}}*
📍 Total Area: {{5}} sq m
💰 Total Amount: ₹{{6}}
📅 Payment Due: {{7}}

*Next Step - Complete Payment:*
Please complete your payment using the link below:
{{8}}

⚠️ *Important:* Payment must be completed by {{7}} to secure your stalls.

For assistance, contact us or reply to this message.

Best regards,
*SGCCI Team*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Allotted Stalls (formatted list)
4. `{{4}}` - Booking Code
5. `{{5}}` - Total Area
6. `{{6}}` - Total Amount with GST
7. `{{7}}` - Payment Due Date (first occurrence)
8. `{{8}}` - Payment Link URL
9. `{{9}}` - Payment Due Date (second occurrence - same as {{7}})

---

## Template 3: Booking Rejected

**Template Name:** `booking_rejected`
**Category:** UTILITY
**Language:** English

### Message:

```
Hello {{1}},

We regret to inform you that your booking inquiry for *{{2}}* could not be approved.

*Booking Details:*
📋 Booking Code: *{{3}}*
🗓️ Requested Stalls: {{4}}

*Reason:*
{{5}}

We appreciate your interest and encourage you to:
• Contact us for alternative options
• Submit a new inquiry with different requirements

For further assistance, please reach out to our team.

Thank you for your understanding.

Best regards,
*SGCCI Team*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Booking Code
4. `{{4}}` - Requested Stalls
5. `{{5}}` - Rejection Reason

---

## Template 4: Payment Success

**Template Name:** `payment_success`
**Category:** UTILITY
**Language:** English

### Message:

```
🎉 Payment Confirmed! {{1}}

Your payment for *{{2}}* has been successfully received!

*Payment Details:*
📋 Booking Code: *{{3}}*
💰 Amount Paid: ₹{{4}}
📅 Payment Date: {{5}}

*Your Confirmed Stalls:*
{{6}}

Your stall booking is now complete! You will receive further details about the exhibition setup and participation guidelines soon.

For any queries, feel free to contact us.

Thank you for choosing SGCCI!

Best regards,
*SGCCI Team*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Booking Code
4. `{{4}}` - Amount Paid
5. `{{5}}` - Payment Date
6. `{{6}}` - Confirmed Stalls (formatted list)

---

## Template 5: Partial Payment Received

**Template Name:** `partial_payment_received`
**Category:** UTILITY
**Language:** English

### Message:

```
Hello {{1}},

Thank you for your payment! We have received ₹{{2}} for your booking.

*Payment Status:*
📋 Booking Code: *{{3}}*
💰 Remaining Amount: ₹{{4}}
📅 Final Payment Due: {{5}}

⚠️ *Important:* Please complete the remaining payment by {{5}} to confirm your booking.

For any queries, contact us or reply to this message.

Thank you,
*SGCCI Team*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Amount Paid (formatted)
3. `{{3}}` - Booking Code
4. `{{4}}` - Remaining Amount (formatted)
5. `{{5}}` - Payment Deadline Date

---

## Template 6: Payment Reminder

**Template Name:** `payment_reminder`
**Category:** UTILITY
**Language:** English

### Message:

```
Hello {{1}},

This is a reminder about your pending payment for booking *{{2}}*.

*Payment Details:*
💰 Remaining Amount: ₹{{3}}
📅 Payment Deadline: {{4}}
⏰ Days Remaining: {{5}}

*Your Stalls:*
{{6}}

⚠️ *Action Required:* Please complete the payment to secure your booking.

For assistance, contact us or reply to this message.

Best regards,
*SGCCI Team*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Booking Code
3. `{{3}}` - Remaining Amount (formatted)
4. `{{4}}` - Payment Deadline Date
5. `{{5}}` - Days Until Deadline
6. `{{6}}` - Allotted Stalls (formatted list)

---

---

## Template 6: Staff - Booking Approved & Payment Link Sent (Internal)

**Template Name:** `staff_booking_confirmation_payment`
**Campaign Name:** `staff_booking_confirmationpayment`
**Category:** UTILITY
**Language:** English

### Message:

```
📋 Booking Approved

Customer: {{1}}
Exhibition: {{2}}
Allotted Stalls: {{3}}

*Booking Details:*
📋 Booking Code: {{4}}
📍 Total Area: {{5}} sq m
💰 Amount: ₹{{6}}
📅 Payment Due: {{7}}

*Payment Link:* {{8}}

⚠️ Status: Payment Pending
Payment Deadline: {{9}}

*SGCCI Admin System*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Allotted Stalls (comma-separated list)
4. `{{4}}` - Booking Code
5. `{{5}}` - Total Area
6. `{{6}}` - Total Amount with GST
7. `{{7}}` - Payment Due Date
8. `{{8}}` - Payment Link URL
9. `{{9}}` - Payment Due Date (repeated)

---

## Template 7: Staff - New Booking Received (Internal)

**Template Name:** `staff_booking_received`
**Category:** UTILITY
**Language:** English

### Message:

```
🔔 New Booking Alert

Customer: {{1}}
Exhibition: {{2}}
Stalls Requested: {{3}}

*Booking Details:*
📋 Booking Code: *{{4}}*
📍 Total Area: {{5}} sq m
💰 Amount: ₹{{6}}

⚠️ Status: Pending Review
Action Required: Please review and approve/reject this booking in the admin panel.

*SGCCI Admin System*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Selected Stalls (comma-separated list)
4. `{{4}}` - Booking Code
5. `{{5}}` - Total Area
6. `{{6}}` - Total Amount with GST

---

## Template 8: Staff - Payment Received (Internal)

**Template Name:** `staff_payment_success`
**Campaign Name:** `invoicestatus`
**Category:** UTILITY
**Language:** English

### Message:

```
💰 Payment Received

Customer: {{1}}
Exhibition: {{2}}
Booking Code: {{3}}
Amount Paid: ₹{{4}}
Payment Date: {{5}}

Confirmed Stalls: {{6}}

Status: Payment Complete
Next Step: Process stall allocation and send confirmation documents.

*SGCCI Admin System*
```

### Variables:

1. `{{1}}` - Contact Person Name
2. `{{2}}` - Exhibition Title
3. `{{3}}` - Booking Code
4. `{{4}}` - Amount Paid
5. `{{5}}` - Payment Date
6. `{{6}}` - Confirmed Stalls (comma-separated list)

---

## Implementation Notes

### WhatsApp Business API Format

When submitting to Meta/WhatsApp for approval, use this structure:

```json
{
	"name": "booking_received",
	"language": "en",
	"category": "TRANSACTIONAL",
	"components": [
		{
			"type": "BODY",
			"text": "Hello {{1}}, Thank you for your booking inquiry for *{{2}}*! We have received your request for the following stalls: {{3}}...",
			"example": {
				"body_text": [
					[
						"John Doe",
						"Auto Expo 2025",
						"A1, A2, B3",
						"ABC12345",
						"45",
						"67,500.00"
					]
				]
			}
		}
	]
}
```

### Usage in Laravel

```php
// Example: Send booking received notification
Log::channel('whatsapp')->info('WhatsApp booking received notification', [
    'template' => 'booking_received',
    'recipient' => $booking->phone_code . $booking->phone_number,
    'variables' => [
        $booking->contact_person,
        $booking->exhibition->title,
        implode(', ', $booking->selected_stalls),
        $booking->booking_code,
        $booking->total_area,
        number_format((float) $booking->total_with_gst, 2),
    ],
]);
```

### Character Limits

-   Template names: 512 characters max
-   Template content: 1024 characters max
-   Variables: Keep concise for better delivery

### Formatting Rules

-   Use `*bold*` for emphasis
-   Use `_italic_` for secondary emphasis
-   Use emojis sparingly for better readability
-   Keep messages clear and concise
-   Include call-to-action when needed

### Testing Variables

Before submitting templates, test with sample data:

**Booking Received:**

-   John Doe, Auto Expo 2025, A1-A2-B3, XYZ12345, 45, 67,500.00

**Booking Confirmation:**

-   John Doe, Auto Expo 2025, A1, A2, B3, XYZ12345, 45, 67,500.00, Oct 15, 2025, https://sgcci.test/payment/XYZ12345

**Booking Rejected:**

-   John Doe, Auto Expo 2025, XYZ12345, A1, A2, B3, The requested stalls are already allotted to another exhibitor.
