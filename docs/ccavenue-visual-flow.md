# 🎯 CCAvenue Payment Integration - Visual Flow

## 🔄 Payment Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER JOURNEY                              │
└─────────────────────────────────────────────────────────────────┘

1. USER INITIATES PAYMENT
   └─> Clicks "Pay Now" button with booking code
       URL: /payment/{BOOKING_CODE}

2. SYSTEM VALIDATES BOOKING
   ├─> Checks if booking exists
   ├─> Verifies booking is approved/allotted
   ├─> Ensures payment not already completed
   └─> Updates payment_initiated_at timestamp

3. ENCRYPTION & REDIRECT
   ├─> CCAvenueService prepares merchant data
   ├─> Data encrypted with AES-128-CBC
   ├─> Beautiful redirect page shown (1.5s)
   └─> Form auto-submits to CCAvenue

4. CCAVENUE PAYMENT PAGE
   ├─> User enters payment details
   ├─> CCAvenue processes payment
   └─> Encrypts response data

5. CALLBACK TO YOUR SITE
   └─> POST to /payment/response with encrypted data

6. RESPONSE PROCESSING
   ├─> Decrypt response data
   ├─> Parse payment details
   ├─> Update booking record
   │   ├─> payment_transaction_id
   │   ├─> payment_status
   │   ├─> payment_method
   │   ├─> payment_amount
   │   ├─> payment_completed_at (if success)
   │   └─> payment_response (full JSON)
   └─> Log transaction

7. NOTIFICATIONS (if successful)
   └─> WhatsApp message sent via queue

8. SHOW RESULT PAGE
   └─> Success or failure page with transaction details
```

## 📋 Data Flow

```
┌──────────────┐
│   Booking    │
│    Model     │
└──────┬───────┘
       │
       ▼
┌──────────────────────────────────────────────┐
│        PaymentController@initiate             │
│  • Validates booking eligibility              │
│  • Marks payment as initiated                 │
└──────┬───────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────┐
│        CCAvenueService                        │
│  • preparePaymentData()                       │
│    - merchant_id                              │
│    - order_id (booking_code)                  │
│    - amount                                   │
│    - billing details                          │
│    - redirect URLs                            │
│  • encrypt()                                  │
└──────┬───────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────┐
│        Redirect Page                          │
│  • Shows booking summary                      │
│  • Hidden form with:                          │
│    - encRequest (encrypted data)              │
│    - access_code                              │
│  • Auto-submits after 1.5s                    │
└──────┬───────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────┐
│        CCAvenue Gateway                       │
│  (External - secure.ccavenue.com)             │
│  • User completes payment                     │
│  • Encrypts response                          │
└──────┬───────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────┐
│        PaymentController@response             │
│  • Receives encrypted response                │
│  • Calls CCAvenueService->decrypt()           │
│  • Updates booking with payment data          │
│  • Dispatches WhatsApp notification           │
└──────┬───────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────┐
│        Response Page                          │
│  • Shows success/failure message              │
│  • Displays transaction details               │
│  • Provides "Return to Home" button           │
└──────────────────────────────────────────────┘
```

## 🔐 Security Flow

```
┌────────────────────────────────────────────────────┐
│            ENCRYPTION (Outgoing)                    │
└────────────────────────────────────────────────────┘

Payment Data (Plain Text)
    ↓
MD5 Hash of Working Key
    ↓
AES-128-CBC Encryption
    ↓
Convert to Hexadecimal
    ↓
Send to CCAvenue


┌────────────────────────────────────────────────────┐
│            DECRYPTION (Incoming)                    │
└────────────────────────────────────────────────────┘

Encrypted Response from CCAvenue
    ↓
Convert from Hexadecimal
    ↓
MD5 Hash of Working Key
    ↓
AES-128-CBC Decryption
    ↓
Parse URL-encoded String
    ↓
Array of Response Data
```

## 💾 Database Updates

```
BEFORE PAYMENT INITIATION:
┌────────────────────────────────────────┐
│ Booking #BOOK123456                    │
├────────────────────────────────────────┤
│ status: allotted                       │
│ total_with_gst: 67500.00              │
│ payment_initiated_at: NULL             │
│ payment_completed_at: NULL             │
│ payment_status: NULL                   │
│ payment_transaction_id: NULL           │
└────────────────────────────────────────┘
          ↓ User initiates payment
┌────────────────────────────────────────┐
│ Booking #BOOK123456                    │
├────────────────────────────────────────┤
│ status: allotted                       │
│ total_with_gst: 67500.00              │
│ payment_initiated_at: 2025-10-09 ...  │ ← Updated
│ payment_completed_at: NULL             │
│ payment_status: NULL                   │
│ payment_transaction_id: NULL           │
└────────────────────────────────────────┘
          ↓ Payment successful
┌────────────────────────────────────────┐
│ Booking #BOOK123456                    │
├────────────────────────────────────────┤
│ status: allotted                       │
│ total_with_gst: 67500.00              │
│ payment_initiated_at: 2025-10-09 ...  │
│ payment_completed_at: 2025-10-09 ...  │ ← Updated
│ payment_status: Success                │ ← Updated
│ payment_transaction_id: 123456789...   │ ← Updated
│ payment_tracking_id: 123456789...      │ ← Updated
│ payment_bank_ref_no: ABC123456         │ ← Updated
│ payment_method: Credit Card            │ ← Updated
│ payment_amount: 67500.00               │ ← Updated
│ payment_response: {...}                │ ← Updated (JSON)
└────────────────────────────────────────┘
```

## 🎨 User Interface Flow

```
┌─────────────────────────────────────────┐
│      BOOKING CONFIRMATION PAGE          │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  [Pay Now - ₹67,500.00]         │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
                 ↓ Click
┌─────────────────────────────────────────┐
│       PAYMENT REDIRECT PAGE             │
│                                         │
│          🔄 Loading...                  │
│                                         │
│  Redirecting to Payment Gateway         │
│                                         │
│  Booking Code: BOOK123456               │
│  Exhibition: Auto Expo 2025             │
│  Amount: ₹67,500.00                     │
│                                         │
│  (Auto-redirects in 1.5 seconds)        │
└─────────────────────────────────────────┘
                 ↓ Auto-submit
┌─────────────────────────────────────────┐
│       CCAVENUE PAYMENT PAGE             │
│        (External Gateway)               │
│                                         │
│  Card Number: [____________]            │
│  CVV: [___]  Expiry: [__/__]           │
│                                         │
│  [ Pay ₹67,500.00 ]                    │
└─────────────────────────────────────────┘
                 ↓ Submit
┌─────────────────────────────────────────┐
│       PAYMENT SUCCESS PAGE              │
│                                         │
│              ✅                         │
│                                         │
│         Payment Successful              │
│                                         │
│  Thank you! Your payment has been       │
│  processed successfully.                │
│                                         │
│  ─────────────────────────────          │
│  Booking Code: BOOK123456               │
│  Exhibition: Auto Expo 2025             │
│  Order Status: Success                  │
│  Transaction ID: 123456789012345        │
│  Bank Reference: ABC123456              │
│  Amount: ₹67,500.00                     │
│  Payment Method: Credit Card            │
│  ─────────────────────────────          │
│                                         │
│  A confirmation has been sent to your   │
│  registered email and WhatsApp number.  │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │     [Return to Home]            │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

## 🧪 Testing Scenarios

```
SCENARIO 1: Successful Payment
────────────────────────────
1. Create booking → BOOK123456
2. Visit /payment/BOOK123456
3. Use card: 4111111111111111
4. Payment success ✓
5. Database updated ✓
6. WhatsApp sent ✓
7. Success page shown ✓

SCENARIO 2: Failed Payment
────────────────────────────
1. Create booking → BOOK123457
2. Visit /payment/BOOK123457
3. Use card: 4000000000000002
4. Payment fails ✗
5. Database updated with failure ✓
6. No WhatsApp sent ✓
7. Failure page shown ✓

SCENARIO 3: Duplicate Payment
────────────────────────────
1. Booking BOOK123456 (already paid)
2. Visit /payment/BOOK123456
3. Redirected to thank you page
4. Message: "Payment already completed"

SCENARIO 4: Unauthorized Booking
────────────────────────────
1. Booking BOOK123458 (pending approval)
2. Visit /payment/BOOK123458
3. Redirected to thank you page
4. Message: "Booking not approved for payment"

SCENARIO 5: Payment Cancellation
────────────────────────────
1. Create booking → BOOK123459
2. Visit /payment/BOOK123459
3. Click "Cancel" on CCAvenue
4. Redirected to /payment/cancel
5. Message: "Payment was cancelled"
```

## 📊 Status Codes Reference

```
┌─────────────┬──────────────────────────────────┐
│   Status    │          Meaning                 │
├─────────────┼──────────────────────────────────┤
│  Success    │  Payment completed successfully  │
│  Failure    │  Payment failed                  │
│  Aborted    │  User cancelled payment          │
│  Invalid    │  Invalid request/response        │
└─────────────┴──────────────────────────────────┘

Payment Methods Returned:
• Credit Card
• Debit Card
• Net Banking
• UPI
• Wallet
• EMI
```

## 🔍 Logging & Monitoring

```
Log Entries Created:
────────────────────

1. Payment Initiated
   → booking_code, amount

2. CCAvenue Response Parsed
   → order_id, order_status, tracking_id

3. CCAvenue Payment Response
   → booking_code, status, tracking_id

4. Payment Cancelled (if applicable)
   → booking_code

Errors Logged:
──────────────

1. CCAvenue Response Missing
2. Payment Response Processing Failed
   → Full error message & stack trace
3. Payment Cancellation Processing Failed
```

## 🎁 Helper Methods Quick Reference

```php
// On Booking model

$booking->isPaymentCompleted()
// Returns: bool
// Usage: Check if payment is done

$booking->isPaymentPending()
// Returns: bool
// Usage: Check if payment is needed

$booking->getPaymentUrl()
// Returns: string (URL)
// Usage: Get payment initiation URL
```

---

**🚀 Ready to Process Payments!**

Visit: `/payment/{BOOKING_CODE}` to start testing
