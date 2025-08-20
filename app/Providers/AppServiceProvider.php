<?php

namespace App\Providers;

use App\Services\HotelAggregatorService;
use App\Services\SupplierAService;
use App\Services\SupplierBService;
use App\Services\SupplierCService;
use App\Services\SupplierDService;
use App\Services\SupplierServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {
        
    }

    public function boot(): void
    {
    }
}
