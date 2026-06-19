Welcome, {{ $name }}!

Thanks for joining {{ config('app.name') }}.

@if(!empty($welcomeUrl))
Visit: {{ $welcomeUrl }}
@endif

If you have any questions, reply to this email.

— The {{ config('app.name') }} Team
