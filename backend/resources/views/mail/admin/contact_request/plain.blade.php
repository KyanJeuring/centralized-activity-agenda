New registration request

Name: {{ $request->name }}
Email: {{ $request->email }}
Organisation: {{ $request->organisation ?? 'N/A' }}
Intent: {{ ucfirst($request->intent) }}

Message:
{{ $request->message }}

Approve: {{ $approveUrl }}
Reject: {{ $rejectUrl }}
