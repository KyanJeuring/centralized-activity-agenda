<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <h1>New registration request</h1>

    <p>A new club registration request has been submitted:</p>

    <ul>
        <li><strong>Name:</strong> {{ $request->name }}</li>
        <li><strong>Email:</strong> {{ $request->email }}</li>
        <li><strong>Organisation:</strong> {{ $request->organisation ?? 'N/A' }}</li>
        <li><strong>Intent:</strong> {{ ucfirst($request->intent) }}</li>
    </ul>

    <p>Message:</p>
    <p>{{ nl2br(e($request->message)) }}</p>

    <p>Please review the request and choose an action:</p>

    <p>
        <a href="{{ $approveUrl }}" style="display:inline-block;padding:12px 20px;background:#22c55e;color:#ffffff;text-decoration:none;border-radius:4px;">Approve registration</a>
    </p>
    <p>
        <a href="{{ $rejectUrl }}" style="display:inline-block;padding:12px 20px;background:#ef4444;color:#ffffff;text-decoration:none;border-radius:4px;">Reject registration</a>
    </p>

    <p>If the buttons do not work, copy and paste the URLs into your browser:</p>
    <p><a href="{{ $approveUrl }}">{{ $approveUrl }}</a></p>
    <p><a href="{{ $rejectUrl }}">{{ $rejectUrl }}</a></p>
</body>
</html>
