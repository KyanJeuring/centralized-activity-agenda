<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'organizer',
        'start_date',
        'description',
        'location',
        'url',
        'app_id',
        'img',
    ];

    protected $casts = [
        'start_date' => 'datetime',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class, 'app_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                // If the user is authenticated via API, limit events to the club they own
                $user = auth()->user();
                // Assuming the user has one club. We can get their club's ID.
                $club = Club::where('owner_user_id', $user->id)->first();
                if ($club) {
                    $builder->where('events.app_id', $club->id);
                }
            }
        });
    }
}
