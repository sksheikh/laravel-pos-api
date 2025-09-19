<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
     public function __construct(private StockLevelLow $event) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low Stock Alert')
            ->line("Product {$this->event->product->name} is running low in {$this->event->warehouse->name}")
            ->line("Current stock: {$this->event->currentStock}")
            ->line("Minimum threshold: {$this->event->minimumThreshold}")
            ->action('View Inventory', url('/inventory'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->event->product->id,
            'warehouse_id' => $this->event->warehouse->id,
            'current_stock' => $this->event->currentStock,
            'message' => "Low stock alert for {$this->event->product->name}"
        ];
    }
}
