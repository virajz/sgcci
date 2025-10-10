# Test Booking Command

## Overview

The `booking:create-test` Artisan command creates a fully approved test booking that's ready for payment testing.

## Usage

### Basic Usage (Default Values)

```bash
php artisan booking:create-test
```

This creates a test booking with:

-   **Exhibition**: ID 1 (Auto Expo)
-   **Stall**: 101 (default)
-   **Status**: Payment Pending
-   **Approvals**: Both admin levels approved
-   **Payment Link**: Ready to use

### Custom Stalls

```bash
php artisan booking:create-test --stalls=202 --stalls=203
```

### Custom Exhibition

```bash
php artisan booking:create-test --exhibition=2
```

## Command Output

The command displays:

-   ✅ Booking Code
-   Exhibition Details
-   Contact Information
-   Selected Stalls
-   Pricing Breakdown (auto-calculated based on stalls)
-   Approval Details
-   Payment Link (ready to use)

## Example Output

```
✅ Test booking created successfully!

+-------------------------+--------------------------------+
| Field                   | Value                          |
+-------------------------+--------------------------------+
| Booking Code            | UJK9Y7FB                       |
| Exhibition              | Auto Expo                      |
| Status                  | Payment Pending                |
| Contact Person          | Test User                      |
| Email                   | test@example.com               |
| Phone                   | +91 9876543210                 |
| Selected Stalls         | 101                            |
| Total Area              | 36.00 sq m                     |
| Price per sq m          | ₹750.00                        |
| Total Price             | ₹27,000.00                     |
| Discount (5.00%)        | ₹1,350.00                      |
| GST (18%)               | ₹4,617.00                      |
| Total with GST          | ₹30,267.00                     |
| Admin Approved By       | Test User (2025-10-08 16:48)   |
| Super Admin Approved By | Super Admin (2025-10-09 16:48) |
| Payment Due Date        | 2025-10-17 16:48               |
+-------------------------+--------------------------------+

🔗 Payment Link:
https://sgcci.test/payment/UJK9Y7FB

💡 To initiate payment, visit:
https://sgcci.test/payment/UJK9Y7FB
```

## Features

### Automatic Approvals

-   **Admin Approved**: 2 days ago
-   **Super Admin Approved**: 1 day ago
-   **Status**: Payment Pending (ready for payment)

### Pricing

-   Pricing is **auto-calculated** based on selected stalls
-   Includes discounts for:
    -   Previous exhibitors
    -   SGCCI members
-   18% GST applied automatically

### Test Data

-   **Brand**: Test Company Pvt Ltd
-   **Contact Person**: Test User
-   **Email**: test@example.com
-   **Phone**: +91 9876543210
-   **City**: Surat
-   **GST Number**: 24AAAAA0000A1Z5
-   **Product Profile**: 4-wheelers, 2-wheelers
-   **Has Exhibited Before**: Yes (2023, 2024)
-   **SGCCI Member**: Yes (Gold)

## Payment Testing

After creating a test booking:

1. Copy the payment URL from the command output
2. Visit the URL in your browser
3. You'll be redirected to CCAvenue payment gateway
4. Complete the test payment
5. Check the booking status changes to "Payment Completed"

## Requirements

-   At least one admin user (role: 'admin')
-   At least one super admin user (role: 'super_admin')
-   At least one exhibition in the database

## Command Options

| Option         | Default | Description                                                |
| -------------- | ------- | ---------------------------------------------------------- |
| `--exhibition` | 1       | Exhibition ID                                              |
| `--stalls`     | 101     | Stall numbers (can specify multiple)                       |
| `--area`       | 96      | Total area (Note: Overridden by auto-calculation)          |
| `--price`      | 750     | Price per sq m (Note: Overridden by auto-calculation)      |
| `--discount`   | 10      | Discount percentage (Note: Overridden by auto-calculation) |

**Note**: The `area`, `price`, and `discount` options are present but will be overridden by the model's automatic pricing calculation based on the selected stalls and member benefits.
