Welcome, {{ $user->name }}!

Thanks for joining {{ config('app.name') }}. Your account has been created and a club has been assigned to you.

Club name: {{ $club->name }}

API access token:
{{ $token }}

Use this token to authenticate API requests for your club.

If you need help, reply to this email and we'll assist you.

— The {{ config('app.name') }} team
