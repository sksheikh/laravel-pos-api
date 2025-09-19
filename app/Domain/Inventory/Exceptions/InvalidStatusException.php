<?php
namespace App\Domain\Inventory\Exceptions;

class InvalidStatusException extends \Exception
{
    public function __construct(string $message = 'Invalid status for this operation')
    {
        parent::__construct($message);
    }
}
