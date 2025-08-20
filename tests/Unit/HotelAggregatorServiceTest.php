<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\HotelAggregatorService;

class HotelAggregatorServiceTest extends TestCase
{
    protected HotelAggregatorService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://127.0.0.1:8000']);

        $this->svc = $this->app->make(HotelAggregatorService::class);
    }

    /** @test */
    public function it_merges_results_and_returns_paginated_payload()
    {
        Http::fake([
            'http://127.0.0.1:8000/api/mock/supplier-a*' => Http::response([
                ['name' => 'Tulip', 'location' => 'Cairo, Egypt', 'price_per_night' => 150, 'available_rooms' => 3, 'rating' => 4.1],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-b*' => Http::response([
                ['name' => 'Stiegenberger', 'location' => 'Cairo, Egypt', 'price_per_night' => 90, 'available_rooms' => 2, 'rating' => 4.6],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-c*' => Http::response([
                ['name' => 'Hilton', 'location' => 'Cairo, Egypt', 'price_per_night' => 120, 'available_rooms' => 5, 'rating' => 3.9],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-d*' => Http::response([
                ['name' => 'Movenpick', 'location' => 'Cairo, Egypt', 'price_per_night' => 80, 'available_rooms' => 1, 'rating' => 4.0],
            ], 200),
        ]);

        $result = $this->svc->searchHotels([
            'location'   => 'Cairo',
            'check_in'   => '2025-09-01',
            'check_out'  => '2025-09-05',
            'page'       => 1,
            'per_page'   => 2,
            'sort_by'    => 'price',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);

        // Should have 4 total after merge, but only 2 on page 1 (per_page=2)
        $this->assertCount(2, $result['data']);
        $this->assertSame(4, $result['meta']['total']);
        $this->assertSame(2, $result['meta']['per_page']);
        $this->assertSame(1, $result['meta']['current_page']);
        $this->assertSame(2, intval($result['meta']['total_pages']));

        // Check standardized keys exists
        $hotel = $result['data'][0];
        $this->assertArrayHasKey('id', $hotel);
        $this->assertArrayHasKey('name', $hotel);
        $this->assertArrayHasKey('location', $hotel);
        $this->assertArrayHasKey('price_per_night', $hotel);
        $this->assertArrayHasKey('available_rooms', $hotel);
        $this->assertArrayHasKey('rating', $hotel);
        $this->assertArrayHasKey('source', $hotel);
    }

    /** @test */
    public function it_deduplicates_same_hotel_and_keeps_lowest_price()
    {
        // Same (name, location) across A and B -> should keep the cheaper one (from B)
        Http::fake([
            'http://127.0.0.1:8000/api/mock/supplier-a*' => Http::response([
                ['name' => 'Nile View', 'location' => 'Cairo, Egypt', 'price_per_night' => 150, 'available_rooms' => 3, 'rating' => 4.4],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-b*' => Http::response([
                ['name' => 'Nile View', 'location' => 'Cairo, Egypt', 'price_per_night' => 120, 'available_rooms' => 4, 'rating' => 4.3],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-c*' => Http::response([], 200),
            'http://127.0.0.1:8000/api/mock/supplier-d*' => Http::response([], 200),
        ]);

        $result = $this->svc->searchHotels([
            'location' => 'Cairo',
            'sort_by'  => 'price',
            'per_page' => 10,
            'page'     => 1,
        ]);

        $this->assertSame(1, $result['meta']['total']);
        $this->assertCount(1, $result['data']);

        $hotel = $result['data'][0];
        $this->assertSame('Nile View', $hotel['name']);
        $this->assertSame(120.0, $hotel['price_per_night']);
        $this->assertSame('supplier_b', $hotel['source']);
    }

    /** @test */
    public function it_filters_by_price_range_and_guest_count()
    {
        Http::fake([
            'http://127.0.0.1:8000/api/mock/supplier-a*' => Http::response([
                ['name' => 'CheapStay', 'location' => 'Cairo, Egypt', 'price_per_night' => 50,  'available_rooms' => 2,  'rating' => 4.0],
                ['name' => 'MidStay',   'location' => 'Cairo, Egypt', 'price_per_night' => 200, 'available_rooms' => 3,  'rating' => 4.3],
                ['name' => 'LuxStay',   'location' => 'Cairo, Egypt', 'price_per_night' => 500, 'available_rooms' => 10, 'rating' => 4.8],
            ], 200),
            // Others empty
            'http://127.0.0.1:8000/api/mock/supplier-b*' => Http::response([], 200),
            'http://127.0.0.1:8000/api/mock/supplier-c*' => Http::response([], 500),
            'http://127.0.0.1:8000/api/mock/supplier-d*' => Http::response([], 200),
        ]);

        $result = $this->svc->searchHotels([
            'location'  => 'Cairo',
            'min_price' => 60,
            'max_price' => 300,
            'guests'    => 3,      // require available_rooms >= 3
            'sort_by'   => 'price',
            'per_page'  => 10,
            'page'      => 1,
        ]);

        // Only "MidStay" should pass (price 200 within range, rooms 3 >= guests 3)
        $this->assertSame(1, $result['meta']['total']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('MidStay', $result['data'][0]['name']);
    }

    /** @test */
    public function it_sorts_by_price_ascending_by_default()
    {
        Http::fake([
            'http://127.0.0.1:8000/api/mock/supplier-a*' => Http::response([
                ['name' => 'A', 'location' => 'Cairo, Egypt', 'price_per_night' => 200, 'available_rooms' => 5, 'rating' => 3.0],
                ['name' => 'B', 'location' => 'Cairo, Egypt', 'price_per_night' => 100, 'available_rooms' => 5, 'rating' => 4.8],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-b*' => Http::response([], 200),
            'http://127.0.0.1:8000/api/mock/supplier-c*' => Http::response([], 200),
            'http://127.0.0.1:8000/api/mock/supplier-d*' => Http::response([], 200),
        ]);

        $result = $this->svc->searchHotels([
            'location' => 'Cairo',
            // no sort_by provided -> defaults to price
            'per_page' => 10,
            'page'     => 1,
        ]);

        $this->assertSame(['B', 'A'], array_column($result['data'], 'name'));
    }

    /** @test */
    public function it_sorts_by_rating_desc_when_requested()
    {
        Http::fake([
            'http://127.0.0.1:8000/api/mock/supplier-a*' => Http::response([
                ['name' => 'Low',  'location' => 'Cairo, Egypt', 'price_per_night' => 180, 'available_rooms' => 4, 'rating' => 3.0],
                ['name' => 'High', 'location' => 'Cairo, Egypt', 'price_per_night' => 120, 'available_rooms' => 4, 'rating' => 4.9],
            ], 200),
            'http://127.0.0.1:8000/api/mock/supplier-b*' => Http::response([], 200),
            'http://127.0.0.1:8000/api/mock/supplier-c*' => Http::response([], 200),
            'http://127.0.0.1:8000/api/mock/supplier-d*' => Http::response([], 200),
        ]);

        $result = $this->svc->searchHotels([
            'location' => 'Cairo',
            'sort_by'  => 'rating',
            'per_page' => 10,
            'page'     => 1,
        ]);

        $this->assertSame(['High', 'Low'], collect($result['data'])->pluck('name')->toArray() );
    }
}
