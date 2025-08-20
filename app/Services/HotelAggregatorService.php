<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
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
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $sortBy = $filters['sort_by'] ?? 'price';
        $query = http_build_query($filters);
        $responses = Http::timeout(3)
            ->connectTimeout(1)
            ->acceptJson()
            ->pool(fn(Pool $pool) => 
                collect($this->suppliers)->map(fn($url, $supplier) => 
                    $pool->as($supplier)->get(config('app.url') . $url.'?' . $query)
                )->toArray()
            );
        
        $res = $this->mergeAndFilter($responses, $filters, $sortBy);

        return $this->paginateResults($res, $perPage, $page); 
    }

    private function paginateResults(array $results, int $perPage, int $page): array
    {
        
        $total = count($results);
        
        return [
            'data' => array_slice($results, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    private function processResponse($hotels, string $supplier): array
    {
 
        $hotels = array_map(fn($hotel) => [
            'id' => md5(strtolower($hotel['name'].$hotel['location'])),
            'name' => $hotel['name'] ?? '',
            'location' => $hotel['location'] ?? '',
            'price_per_night' => (float) ($hotel['price_per_night'] ?? 0),
            'available_rooms' => (int) ($hotel['available_rooms'] ?? 0),
            'rating' => (float) ($hotel['rating'] ?? 0),
            'source' => $supplier
        ], $hotels);
        return $hotels ? array_values($hotels) : [];
    }

    private function handleError(\Throwable $e, string $supplier): array
    {
        Log::error("Error fetching hotels from {$supplier}", [
            'error' => $e->getMessage()
        ]);
        return [];
    }

    private function mergeAndFilter(array $responses, array $filters, string $sortBy): array
    {
        $collection = LazyCollection::make($responses)
            ->flatMap(function ($response, $supplier) {
                if ($response->successful()) {
                    return $this->processResponse($response->json(), $supplier);
                }
                $this->handleError($response, $supplier);
                return [];
            })
            ->filter(fn($hotel) => $this->matchesFilters($hotel, $filters))
            ->groupBy('id') // ensures no duplicates
            ->map(function ($hotels) {
                return $hotels->sortBy('price_per_night')->first(); // ensures the lowest price duplicates are kept
            });

        // Sort by rating (highest first) or price (lowest first)
        return match($sortBy) {
            'rating' => $collection->sortByDesc('rating')->values()->all(),
            default => $collection->sortBy('price_per_night')->values()->all()
        };
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
