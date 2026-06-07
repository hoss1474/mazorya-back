<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProjectPayment extends Model
{
    protected $table = 'client_project_payments';

    protected $fillable = [
        'client_project_id',
        'title',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'paid_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
