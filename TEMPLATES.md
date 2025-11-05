## Template 1: Booking Received (Acknowledgment)

_Template Name:_ booking*received
\_Category:* UTILITY
_Language:_ English

### Message:

Hello {{1}},

Thank you for your booking inquiry for _{{2}}_!

We have received your request for the following stalls:
{{3}}

_Booking Details:_
📋 Booking Code: _{{4}}_
📍 Total Area: {{5}} sq m
💰 Amount: ₹{{6}}

Your booking is currently under review by our admin team. You will receive a confirmation message once it has been processed.

For any queries, please contact us or reply to this message.

Thank you,
_SGCCI Team_

### Variables:

1. {{1}} - Contact Person Name
2. {{2}} - Exhibition Title
3. {{3}} - Selected Stalls (comma-separated list)
4. {{4}} - Booking Code
5. {{5}} - Total Area
6. {{6}} - Total Amount with GST

---

## Template 2: Booking Confirmation with Payment Link

_Template Name:_ booking*confirmation_payment
\_Category:* UTILITY
_Language:_ English

### Message:

🎉 Congratulations {{1}}!

Your booking for _{{2}}_ has been _CONFIRMED_!

_Allotted Stalls:_
{{3}}

_Booking Summary:_
📋 Booking Code: _{{4}}_
📍 Total Area: {{5}} sq m
💰 Total Amount: ₹{{6}}
📅 Payment Due: {{7}}

_Next Step - Complete Payment:_
Please complete your payment using the link below:
{{8}}

⚠️ _Important:_ Payment must be completed by {{7}} to secure your stalls.

For assistance, contact us or reply to this message.

Best regards,
_SGCCI Team_

### Variables:

1. {{1}} - Contact Person Name
2. {{2}} - Exhibition Title
3. {{3}} - Allotted Stalls (formatted list)
4. {{4}} - Booking Code
5. {{5}} - Total Area
6. {{6}} - Total Amount with GST
7. {{7}} - Payment Due Date
8. {{8}} - Payment Link URL

---

## Template 3: Booking Rejected

_Template Name:_ booking*rejected
\_Category:* UTILITY
_Language:_ English

### Message:

Hello {{1}},

We regret to inform you that your booking inquiry for _{{2}}_ could not be approved.

_Booking Details:_
📋 Booking Code: _{{3}}_
🗓️ Requested Stalls: {{4}}

_Reason:_
{{5}}

We appreciate your interest and encourage you to:
• Contact us for alternative options
• Submit a new inquiry with different requirements

For further assistance, please reach out to our team.

Thank you for your understanding.

Best regards,
_SGCCI Team_

### Variables:

1. {{1}} - Contact Person Name
2. {{2}} - Exhibition Title
3. {{3}} - Booking Code
4. {{4}} - Requested Stalls
5. {{5}} - Rejection Reason

---

## Template 4: Payment Success

_Template Name:_ payment*success
\_Category:* UTILITY
_Language:_ English

### Message:

🎉 Payment Confirmed! {{1}}

Your payment for _{{2}}_ has been successfully received!

_Payment Details:_
📋 Booking Code: _{{3}}_
💰 Amount Paid: ₹{{4}}
📅 Payment Date: {{5}}

_Your Confirmed Stalls:_
{{6}}

Your stall booking is now complete! You will receive further details about the exhibition setup and participation guidelines soon.

For any queries, feel free to contact us.

Thank you for choosing SGCCI!

Best regards,
_SGCCI Team_

### Variables:

1. {{1}} - Contact Person Name
2. {{2}} - Exhibition Title
3. {{3}} - Booking Code
4. {{4}} - Amount Paid
5. {{5}} - Payment Date
6. {{6}} - Confirmed Stalls (formatted list)

---
