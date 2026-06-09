<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? 'Test Email' }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
  </style>
</head>
<body>
  <h1>{{ $subject ?? 'Test Email' }}</h1>
  <p>{{ $body }}</p>
</body>
</html>
