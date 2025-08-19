<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

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

        return $this->mergeAndFilter($responses, $filters);
    }

    private function processResponse($response, string $supplier): array
    {
        if (!$response->successful()) {
            Log::error("Failed to fetch hotels from {$supplier}", [
                'status' => $response->status()
            ]);
            return [];
        }

        $hotels = $response->json() ?? [];
        
        return array_map(fn($hotel) => [
            'id' => md5(strtolower($hotel['name'].$hotel['location'])),
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
            'error' => $e->getMessage()
        ]);
        return [];
    }

    private function mergeAndFilter(array $responses, array $filters): array
    {
        return LazyCollection::make($responses)
            ->flatten(1)
            ->filter(fn($hotel) => $this->matchesFilters($hotel, $filters))
            ->groupBy('id')
            ->map(function ($hotels) {
                return $hotels->sortBy('price_per_night')->first();
            })
            ->sortBy('price_per_night')
            ->values()
            ->all();
    }

    private function matchesFilters(array $hotel, array $filters): bool
    {
        // Skip filtering if no filters are provided
        if (empty($filters)) {
            return true;
        }

        // Price range filter
        if (isset($filters['min_price']) && $hotel['price_per_night'] < (float) $filters['min_price']) {
            return false;
        }
        if (isset($filters['max_price']) && $hotel['price_per_night'] > (float) $filters['max_price']) {
            return false;
        }

        // Guest count filter (available rooms)
        if (isset($filters['guests']) && $hotel['available_rooms'] < (int) $filters['guests']) {
            return false;
        }

        return true;
    }
}
