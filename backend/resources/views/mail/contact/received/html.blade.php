<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <h1>Request received</h1>

    <p>Hi {{ $request->name }},</p>

    <p>Thanks for your registration request.</p>

    <p>We've sent your request to the system administrator for review. You will receive another email once the request is approved or rejected.</p>

    <p>Request details:</p>
    <ul>
        <li><strong>Email:</strong> {{ $request->email }}</li>
        <li><strong>Organisation:</strong> {{ $request->organisation ?? 'N/A' }}</li>
        <li><strong>Intent:</strong> {{ ucfirst($request->intent) }}</li>
    </ul>

    <p>Message:</p>
    <p>{{ nl2br(e($request->message)) }}</p>
</body>
</html>
