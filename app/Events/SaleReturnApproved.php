<?php

namespace App\Events;

use App\Models\User;
use App\Models\SaleReturn;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SaleReturnApproved
{
     use Dispatchable, SerializesModels;

    public function __construct(
        public SaleReturn $saleReturn,
        public User $approver
    ) {}
}
