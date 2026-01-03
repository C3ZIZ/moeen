<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderJob;
use App\Models\Reminder;
use Illuminate\Console\Command;

class RemindersDispatchDue extends Command
{
    protected $signature = 'reminders:dispatch-due';
    protected $description = 'Dispatch due reminders to the queue';

    public function handle(): int
    {
        Reminder::query()
            ->whereNull('sent_at')
            ->where('runs_at', '<=', now())
            ->orderBy('runs_at')
            ->chunkById(200, function ($reminders) {
                foreach ($reminders as $reminder) {
                    SendReminderJob::dispatch($reminder->id)->onQueue('high');
                }
            });

        $this->info('OK: dispatched due reminders.');
        return self::SUCCESS;
    }
}
