<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;
use App\Infrastructure\Repositories\EloquentSaleRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);
        $this->app->bind(PurchaseRepositoryInterface::class, EloquentPurchaseRepository::class);
        $this->app->bind(SaleReturnRepositoryInterface::class, EloquentSaleReturnRepository::class);
        $this->app->bind(PurchaseReturnRepositoryInterface::class, EloquentPurchaseReturnRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
