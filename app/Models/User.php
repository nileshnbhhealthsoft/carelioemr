<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * Determine whether the user has administrative privileges.
     */
    public function isAdmin(): bool
    {
        if (array_key_exists('is_admin', $this->attributes) && $this->attributes['is_admin'] !== null) {
            return (bool) $this->attributes['is_admin'];
        }

        $adminEmails = config('auth.admin_emails');
        if (!empty($adminEmails)) {
            $whitelist = is_array($adminEmails) ? $adminEmails : array_map('trim', explode(',', $adminEmails));
            return in_array(strtolower($this->email), array_map('strtolower', $whitelist));
        }

        return true;
    }

    /**
     * Accessor for is_admin attribute ($user->is_admin).
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }
}

