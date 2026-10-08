<x-mail::message>
# {{ $intent === 'signup' ? 'Confirm your registration' : 'Log in to slots.tube' }}

Click the button below. This link expires in 30 minutes.

<x-mail::button :url="$url">
{{ $intent === 'signup' ? 'Confirm email' : 'Log in' }}
</x-mail::button>

If you did not request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
