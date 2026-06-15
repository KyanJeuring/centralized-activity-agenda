<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Welcome to {{ config('app.name') }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #333; }
    .container { max-width: 600px; margin: 24px auto; padding: 24px; border: 1px solid #eaeaea; border-radius: 8px; }
    h1 { color: #111; }
    a.button { display: inline-block; padding: 10px 16px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; }
    p.lead { font-size: 16px; }
    footer { font-size: 12px; color: #666; margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Welcome, {{ $name }}!</h1>
    <p class="lead">Thanks for joining {{ config('app.name') }}. We're excited to have you on board.</p>

    @if(!empty($welcomeUrl))
      <p><a class="button" href="{{ $welcomeUrl }}">Get Started</a></p>
    @endif

    <p>If you have any questions, reply to this email and we'll get back to you.</p>

    <footer>
      — The {{ config('app.name') }} Team
    </footer>
  </div>
</body>
</html>
