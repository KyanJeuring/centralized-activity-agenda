<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            Club::firstOrCreate(
                ['owner_user_id' => $user->id],
                [
                    'name' => 'Club for '.$user->name,
                    'source' => 'system',
                    'type' => 'remote',
                ]
            );
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function club()
    {
        return $this->hasOne(Club::class, 'owner_user_id');
    }

    /**
     * Get the primary club_id for the user
     * 
     * @return string|null
     */
    public function getClubIdAttribute()
    {
        return $this->club()->value('id');
    }
}
