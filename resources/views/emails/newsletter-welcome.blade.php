<x-mail::message>
# Welcome to slots.tube

Thanks for subscribing. We will send you free slots news and bonuses.

<x-mail::button :url="config('app.url')">
Visit slots.tube
</x-mail::button>

[Unsubscribe]({{ $unsubscribeUrl }})

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
