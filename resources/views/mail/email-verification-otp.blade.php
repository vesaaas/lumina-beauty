<x-mail::message>
# Verify your email

Thank you for creating your Lumina Beauty account.

Use the verification code below to confirm your email address:

# {{ $code }}

This code will expire in **10 minutes**.

If you did not create a Lumina Beauty account, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
