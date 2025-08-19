<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupplierAService implements SupplierServiceInterface
{
    private const SUPPLIER_URL = '/api/mock/supplier-a';

    public function fetchHotels(array $filters): array
    {
        try {
            $response = Http::get(config('app.url') . self::SUPPLIER_URL);

            if (!$response->successful()) {
                Log::error('Failed to fetch hotels', [
                    'supplier' => 'A',
                    'status' => $response->status(),
                    'filters' => $filters
                ]);
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                'supplier' => 'A',
                'filters' => $filters
            ]);
            return [];
        }
    }
}
