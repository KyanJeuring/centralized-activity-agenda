<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactRequest extends Model
{
    protected $table = 'contact_requests';

    protected $fillable = [
        'name',
        'email',
        'organisation',
        'message',
        'intent',
        'status',
        'admin_token',
    ];

    protected $hidden = [
        'admin_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContactRequest $contactRequest) {
            if (empty($contactRequest->admin_token)) {
                $contactRequest->admin_token = Str::uuid()->toString();
            }
        });
    }
}
