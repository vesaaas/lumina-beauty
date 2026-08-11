<x-mail::message>
# Confirm your checkout email

Use this code to continue your Lumina Beauty guest checkout:

# {{ $code }}

This code expires in **10 minutes** and can be used only once.

If you did not start a Lumina Beauty checkout, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
