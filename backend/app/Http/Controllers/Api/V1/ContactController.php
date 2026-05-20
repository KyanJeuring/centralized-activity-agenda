<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Mail\ContactNotificationForAdmin;
use App\Mail\ContactRequestRejected;
use App\Models\ContactRequest;
use App\Services\ClientRegistrationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ContactController extends Controller
{
    #[OA\Post(
        path: '/v1/contact',
        operationId: 'submitContactRequest',
        tags: ['Contact'],
        summary: 'Submit a contact request for club registration and admin approval',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'message'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', example: 'jane@example.com'),
                    new OA\Property(property: 'organisation', type: 'string', example: 'Community Hub'),
                    new OA\Property(property: 'message', type: 'string', example: 'I would like to register my club.'),
                    new OA\Property(property: 'intent', type: 'string', example: 'register', description: 'register, change_mode, delete_account'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Contact request created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreContactRequest $request)
    {
        $data = $request->validated();
        $data['admin_token'] = Str::uuid()->toString();

        $contactRequest = ContactRequest::create($data);
        $this->notifyAdmin($contactRequest);

        return response()->json([
            'message' => 'Contact request submitted successfully.',
            'data' => $contactRequest,
        ], 201);
    }

    public function approve(ContactRequest $contactRequest, string $token)
    {
        $this->validateAdminToken($contactRequest, $token);

        if ($contactRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Contact request already processed.',
                'status' => $contactRequest->status,
            ], 409);
        }

        $contactRequest->status = 'approved';
        $contactRequest->save();

        try {
            app(ClientRegistrationService::class)->registerClient(
                $contactRequest->email,
                $contactRequest->organisation ?: $contactRequest->name,
            );
        } catch (\Throwable $exception) {
            $contactRequest->status = 'failed';
            $contactRequest->save();

            return response()->json([
                'message' => 'Approval failed while creating the account.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Contact request approved. Welcome email sent.',
        ]);
    }

    public function reject(ContactRequest $contactRequest, string $token)
    {
        $this->validateAdminToken($contactRequest, $token);

        if ($contactRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Contact request already processed.',
                'status' => $contactRequest->status,
            ], 409);
        }

        $contactRequest->status = 'rejected';
        $contactRequest->save();

        Mail::to($contactRequest->email)->send(new ContactRequestRejected($contactRequest));

        return response()->json([
            'message' => 'Contact request rejected. Requester has been notified.',
        ]);
    }

    protected function notifyAdmin(ContactRequest $contactRequest): void
    {
        $adminEmail = config('app.admin_email', 'thebencemohr@gmail.com');

        $approveUrl = route('contact.approve', [
            'contact_request' => $contactRequest->id,
            'token' => $contactRequest->admin_token,
        ]);

        $rejectUrl = route('contact.reject', [
            'contact_request' => $contactRequest->id,
            'token' => $contactRequest->admin_token,
        ]);

        Mail::to($adminEmail)->send(new ContactNotificationForAdmin($contactRequest, $approveUrl, $rejectUrl));
    }

    protected function validateAdminToken(ContactRequest $contactRequest, string $token): void
    {
        if (! hash_equals($contactRequest->admin_token, $token)) {
            abort(404);
        }
    }
}
