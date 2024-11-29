<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Transformers\UserTransformer;
use Flugg\Responder\Contracts\Transformable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable  implements Transformable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    public function transformer()
    {
        return UserTransformer::class;
    }


    /**
     * Relationships
     */

    // A user belongs to one role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // A user can make many donations
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    // A user can have many activity logs
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }
}
