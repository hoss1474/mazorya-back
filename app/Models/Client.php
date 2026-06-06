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
        'full_name',
        'email',
        'phone',
        'password',
        'avatar',
        'is_active',
        'phone_verified_at',
        'last_login_at',
        'created_start',
        'created_end',
        'status',
        'amount',
        'amount_status_1',
        'amount_status_2',
        'amount_status_3',
        'amount_status_4',
        'description',
        'project_type',
        'project_progress',

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
}
