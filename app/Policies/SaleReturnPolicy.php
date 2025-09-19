<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SaleReturn;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaleReturnPolicy
{
     use HandlesAuthorization;

    public function approve(User $user, SaleReturn $saleReturn): bool
    {
        return $user->can('approve_sale_returns') &&
               $saleReturn->status === SaleReturn::STATUS_PENDING;
    }

    public function create(User $user): bool
    {
        return $user->can('create_sale_returns');
    }
}
