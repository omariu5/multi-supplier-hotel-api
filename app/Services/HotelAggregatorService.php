<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class HotelAggregatorService
{
    private array $suppliers = [
        'supplier_a' => '/api/mock/supplier-a',
        'supplier_b' => '/api/mock/supplier-b',
        'supplier_c' => '/api/mock/supplier-c',
        'supplier_d' => '/api/mock/supplier-d',
    ];

    public function searchHotels(array $filters): array
    {
        $responses = Http::pool(fn($pool) => 
            collect($this->suppliers)->map(fn($url, $supplier) => 
                $pool->get(config('app.url') . $url)
                    ->throw()
                    ->then(
                        fn($response) => $this->processResponse($response, $supplier),
                        fn($e) => $this->handleError($e, $supplier)
                    )
            )->toArray()
        );

        return $this->mergeAndDeduplicate($responses);
    }

    private function processResponse($response, string $supplier): array
    {
        if (!$response->successful()) {
            Log::error("Failed to fetch hotels from {$supplier}", [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
            return [];
        }

        $hotels = $response->json() ?? [];
        
        return array_map(fn($hotel) => [
            'name' => $hotel['name'] ?? '',
            'location' => $hotel['location'] ?? '',
            'price_per_night' => (float) ($hotel['price_per_night'] ?? 0),
            'available_rooms' => (int) ($hotel['available_rooms'] ?? 0),
            'rating' => (float) ($hotel['rating'] ?? 0),
            'source' => $supplier
        ], $hotels);
    }

    private function handleError(\Throwable $e, string $supplier): array
    {
        Log::error("Error fetching hotels from {$supplier}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return [];
    }

    private function mergeAndDeduplicate(array $responses): array
    {
        return collect($responses)
            ->flatten(1)
            ->groupBy('name')
            ->map(function ($hotels) {
                return $hotels->sortBy('price_per_night')->first();
            })
            ->values()
            ->all();
    }
}
