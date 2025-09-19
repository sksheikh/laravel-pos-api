<?php

namespace App\Events;

use App\Models\WarehouseTransfer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WarehouseTransferCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public WarehouseTransfer $transfer) {}
}
