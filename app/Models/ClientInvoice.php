<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientInvoice extends Model
{
    protected $table = 'client_invoices';

    protected $fillable = [
        'client_project_id',
        'invoice_number',
        'file_path',
    ];

    public function project()
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
