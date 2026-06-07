<?php

namespace App\Observers;

use App\Models\ClientProject;

class ClientProjectObserver
{
    public function created(ClientProject $project): void
    {
        $this->generatePayments($project);
    }

    public function updated(ClientProject $project): void
    {
        // فقط اگر مبلغ تغییر کرد دوباره بساز
        if ($project->wasChanged('amount')) {
            $project->payments()->delete();
            $this->generatePayments($project);
        }
    }

    private function generatePayments(ClientProject $project): void
    {
        if (!$project->amount) return;

        $plans = [
            ['title' => 'قسط اول', 'percent' => 30],
            ['title' => 'قسط دوم', 'percent' => 30],
            ['title' => 'قسط سوم', 'percent' => 30],
            ['title' => 'قسط چهارم', 'percent' => 10],
        ];

        foreach ($plans as $plan) {
            $project->payments()->create([
                'title' => $plan['title'],
                'amount' => ($project->amount * $plan['percent']) / 100,
                'status' => 'pending',
            ]);
        }
    }
}
