<?php

namespace App\Jobs;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reminderId) {}

    public $tries = 5;

    public function handle(): void
    {
        DB::transaction(function () {
            $reminder = Reminder::lockForUpdate()->findOrFail($this->reminderId);

            if ($reminder->sent_at) return;

            Log::info('Reminder fired', [
                'reminder_id' => $reminder->id,
                'task_id' => $reminder->task_id,
                'runs_at' => $reminder->runs_at,
            ]);

            $reminder->update(['sent_at' => now()]);
        });
    }
}
