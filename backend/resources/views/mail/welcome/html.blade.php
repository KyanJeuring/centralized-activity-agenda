<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Welcome to {{ config('app.name') }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 0 auto; padding: 24px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; }
    .button { display: inline-block; padding: 12px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px; }
    .footer { color: #6b7280; font-size: 13px; margin-top: 24px; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Welcome, {{ $user->name }}!</h1>
    <p>Thanks for joining {{ config('app.name') }}.</p>

    <p><strong>Club name:</strong> {{ $club->name }}</p>
    <p><strong>API access token:</strong></p>
    <pre style="background:#f3f4f6; padding:12px; border-radius:8px; overflow-x:auto;">{{ $token }}</pre>

    <p>Use this token to authenticate API requests for your club.</p>

    @if(! empty($club->name))
      <p><a class="button" href="{{ config('app.url') }}">Open {{ config('app.name') }}</a></p>
    @endif

    <p>If you need help, reply to this email and we'll assist you.</p>

    <div class="footer">
      — The {{ config('app.name') }} team
    </div>
  </div>
</body>
</html>
