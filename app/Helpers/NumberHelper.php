<?php

namespace App\Helpers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
class NumberHelper
{
 public static function generateSaleNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = Sale::whereDate('created_at', now())->count() + 1;
        return "SALE-{$date}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    public static function generatePurchaseNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = Purchase::whereDate('created_at', now())->count() + 1;
        return "PURCH-{$date}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    public static function generateReturnNumber(string $type = 'sale'): string
    {
        $prefix = $type === 'sale' ? 'SR' : 'PR';
        $date = now()->format('Ymd');
        $model = $type === 'sale' ? SaleReturn::class : PurchaseReturn::class;
        $sequence = $model::whereDate('created_at', now())->count() + 1;
        return "{$prefix}-{$date}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    public static function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        return number_format($amount, 2) . ' ' . $currency;
    }
}
