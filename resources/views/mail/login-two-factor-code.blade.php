<x-mail::message>
# Security verification

Use this code to finish signing in to your Lumina Beauty {{ $context }} account:

# {{ $code }}

This code expires in **10 minutes** and can be used only once.

If you did not try to sign in, change your password and contact support.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
