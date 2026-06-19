Request received

Hi {{ $request->name }},

Thanks for your registration request.

We've sent your request to the system administrator for review. You will receive another email once the request is approved or rejected.

Request details:
Email: {{ $request->email }}
Organisation: {{ $request->organisation ?? 'N/A' }}
Intent: {{ ucfirst($request->intent) }}

Message:
{{ $request->message }}
