<?php

namespace App\Domain\Inventory\Exceptions;
class InsufficientStockException extends \Exception
{
    public function __construct(string $message = 'Insufficient stock available')
    {
        parent::__construct($message);
    }
}
