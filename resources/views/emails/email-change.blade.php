<x-mail::message>
# Confirm your new email

Hi {{ $user->displayName() }}, you asked to use **{{ $newEmail }}** for your slots.tube account.
Click the button below to finish the change. This link expires in 1 hour.

<x-mail::button :url="$url">
Confirm email
</x-mail::button>

If you did not request this, you can ignore this email — your current address stays unchanged.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
