<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendLowStockNotificationJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private StockLevelLow $event) {}

    public function handle(): void
    {
        // Send notification to warehouse manager
        $manager = $this->event->warehouse->manager;

        if ($manager) {
            $manager->notify(new LowStockNotification($this->event));
        }

        // Send email to admin
        Mail::to(config('app.admin_email'))
            ->send(new LowStockMail($this->event));
    }
}
