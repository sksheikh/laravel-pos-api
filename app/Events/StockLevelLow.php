<?php

namespace App\Events;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StockLevelLow
{
     use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public Warehouse $warehouse,
        public int $currentStock,
        public int $minimumThreshold = 10
    ) {}
}
