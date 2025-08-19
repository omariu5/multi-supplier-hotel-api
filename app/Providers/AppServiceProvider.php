<?php

namespace App\Providers;

use App\Services\SupplierAService;
use App\Services\SupplierBService;
use App\Services\SupplierCService;
use App\Services\SupplierDService;
use App\Services\SupplierServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('supplier.a', fn() => new SupplierAService());
        $this->app->bind('supplier.b', fn() => new SupplierBService());
        $this->app->bind('supplier.c', fn() => new SupplierCService());
        $this->app->bind('supplier.d', fn() => new SupplierDService());

        $this->app->bind('suppliers', function ($app) {
            return [
                $app->make('supplier.a'),
                $app->make('supplier.b'),
                $app->make('supplier.c'),
                $app->make('supplier.d'),
            ];
        });

        // $this->app->bind(SupplierServiceInterface::class, SupplierAService::class);
    }

    public function boot(): void
    {
    }
}
