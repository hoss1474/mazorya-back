<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProject extends Model
{
    protected $table = 'client_projects';

    protected $fillable = [
        'client_id',
        'project_name',
        'project_type',
        'project_progress',
        'status',
        'amount',
        'created_start',
        'created_end',
        'description',
    ];

    protected $casts = [
        'created_start' => 'datetime',
        'created_end' => 'datetime',
        'project_progress' => 'integer',
        'amount' => 'decimal:0',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(ClientProjectPayment::class, 'client_project_id');
    }

    public function invoices()
    {
        return $this->hasMany(ClientInvoice::class, 'client_project_id');
    }
    public function generatePayments()
    {
        $amount = $this->amount;
        if (!$amount) return;

        // حذف اقساط قبلی (برای جلوگیری از دوباره‌سازی)
        $this->payments()->delete();

        $plans = [
            ['title' => 'قسط اول', 'percent' => 30],
            ['title' => 'قسط دوم', 'percent' => 30],
            ['title' => 'قسط سوم', 'percent' => 30],
            ['title' => 'قسط چهارم', 'percent' => 10],
        ];

        foreach ($plans as $plan) {
            $this->payments()->create([
                'title' => $plan['title'],
                'amount' => ($amount * $plan['percent']) / 100,
                'status' => 'pending',
            ]);
        }
    }
//    protected static function booted()
//    {
//        static::created(function ($project) {
//            $project->generatePayments();
//        });
//
//        static::updated(function ($project) {
//            // اگر مبلغ تغییر کرد، دوباره بساز
//            if ($project->wasChanged('amount')) {
//                $project->generatePayments();
//            }
//        });
//    }
}
