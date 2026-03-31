<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Club extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'source',
        'issued_key',
        'received_key',
        'type',
        'owner_user_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'app_id');
    }
}
