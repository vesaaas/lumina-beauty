# Known Issues

Only verified or strongly evidenced issues belong here.

## Open

## Email OTP Flow Is Incomplete

Status: OPEN  
Severity: Medium  
Area: Authentication/Email  
Description: Working tree contains initial OTP implementation, but resend/cooldown UX, production email delivery, and full verification policy are not complete.  
Risk: Accounts may be routed into a flow that needs additional UX/security finishing before production.  
Desired behavior: Complete or explicitly defer the OTP milestone with tests.  
Relevant files: `app/Http/Controllers/Auth/EmailVerificationOtpController.php`, `app/Services/EmailVerificationOtpService.php`, `database/migrations/2026_08_08_213500_create_email_verification_otps_table.php`, `resources/views/auth/verify-email-otp.blade.php`  
Notes: Do not configure Gmail as part of this issue unless that phase is explicitly requested.

## In Progress

No separately tracked in-progress issue beyond the current security-authentication branch work noted in [CURRENT_STATE.md](CURRENT_STATE.md).

## Resolved

## Order Status Transitions Are Too Permissive

Status: RESOLVED  
Severity: High  
Area: Commerce/Admin  
Description: `AdminController::updateOrder()` now enforces one-way transitions: `pending -> processing/cancelled`, `processing -> completed/cancelled`, with `completed` and `cancelled` terminal.  
Risk: Resolved risk was inconsistent order history from status reversal.  
Desired behavior: Keep invalid transitions from updating, emailing, or logging `order.status_updated`.  
Relevant files: `app/Http/Controllers/AdminController.php`, `app/Models/Order.php`, `resources/views/admin/orders/show.blade.php`, `tests/Feature/AdminSecurityTest.php`  
Notes: Preserve server-side enforcement.

## Thank-You Page Lacks Order Privacy Guard

Status: RESOLVED  
Severity: Medium  
Area: Commerce/Security  
Description: `StorefrontController::thankYou()` now authorizes authenticated orders by owner and guest orders by same-session checkout access.  
Risk: Resolved risk was arbitrary order confirmation access by changing IDs.  
Desired behavior: Keep owner/session checks or replace only with an equally secure signed/tokenized design.  
Relevant files: `app/Http/Controllers/StorefrontController.php`, `tests/Feature/StorefrontSecurityTest.php`  
Notes: Guest access is session-bound.

## Sensitive Admin Actions Are Inconsistent

Status: RESOLVED  
Severity: Medium  
Area: Admin/Security  
Description: Category deletion, brand deletion, and order status updates all require direct Laravel `current_password` validation and use one reusable admin modal.  
Risk: Resolved risk was inconsistent destructive-action protection and UX.  
Desired behavior: Keep direct server-side password validation for sensitive admin actions.  
Relevant files: `app/Http/Controllers/AdminController.php`, `resources/views/admin/layout.blade.php`, `resources/views/admin/categories/index.blade.php`, `resources/views/admin/brands/index.blade.php`, `resources/views/admin/orders/show.blade.php`, `tests/Feature/AdminSecurityTest.php`  
Notes: Temporary `/admin/confirm-password` route/view were removed.

## Product Physical Deletion Protected

Status: RESOLVED  
Severity: High  
Area: Commerce/Data Integrity  
Description: Products use soft deletes, force delete is blocked, order item product references are restricted, and no admin product delete route is registered.  
Risk: Resolved risk was loss of historical order integrity.  
Desired behavior: Keep this protection in place.  
Relevant files: `app/Models/Product.php`, `database/migrations/2026_07_03_000001_protect_products_from_physical_deletion.php`, `tests/Feature/CheckoutTest.php`  
Notes: See [adr/003-product-soft-deletion.md](adr/003-product-soft-deletion.md).
