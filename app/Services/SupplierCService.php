<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupplierCService implements SupplierServiceInterface
{
    private const SUPPLIER_URL = '/api/mock/supplier-c';

    public function fetchHotels(array $filters): array
    {
        try {
            $response = Http::get(config('app.url') . self::SUPPLIER_URL);

            if (!$response->successful()) {
                Log::error('Supplier C request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'filters' => $filters
                ]);
                return [];
            }

            return $response->json() ?? [];
            
        } catch (\Exception $e) {
            Log::error('Error fetching hotels from Supplier C', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }
}
