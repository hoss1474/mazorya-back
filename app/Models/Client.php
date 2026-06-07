<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Client extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'clients';

    protected $fillable = [
        'company_name',
        'website',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar',
        'is_active',
        'phone_verified_at',
        'last_login_at',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }



    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function projects()
    {
        return $this->hasMany(ClientProject::class);
    }

    public function messages()
    {
        return $this->hasMany(ClientMessage::class);
    }
    public function payments()
    {
        return $this->hasMany(ClientProjectPayment::class, 'client_project_id');
    }
}
