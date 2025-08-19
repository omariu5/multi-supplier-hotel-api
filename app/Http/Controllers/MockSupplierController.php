<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class MockSupplierController extends Controller
{
    public function supplierA(): JsonResponse
    {
        return response()->json([
            [
                'name' => 'Cairo Luxury Hotel',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 120,
                'available_rooms' => 4,
                'rating' => 4.2,
                'source' => 'supplier_a'
            ],
            [
                'name' => 'Pyramids View Inn',
                'location' => 'Giza, Egypt',
                'price_per_night' => 95,
                'available_rooms' => 2,
                'rating' => 4.5,
                'source' => 'supplier_a'
            ],
            [
                'name' => 'Nile River Resort',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 150,
                'available_rooms' => 6,
                'rating' => 4.8,
                'source' => 'supplier_a'
            ]
        ]);
    }

    public function supplierB(): JsonResponse
    {
        return response()->json([
            [
                'name' => 'Alexandria Beach Hotel',
                'location' => 'Alexandria, Egypt',
                'price_per_night' => 110,
                'available_rooms' => 3,
                'rating' => 4.1,
                'source' => 'supplier_b'
            ],
            [
                'name' => 'Mediterranean Resort',
                'location' => 'Alexandria, Egypt',
                'price_per_night' => 130,
                'available_rooms' => 5,
                'rating' => 4.3,
                'source' => 'supplier_b'
            ],
            [
                'name' => 'Coastal Paradise',
                'location' => 'Alexandria, Egypt',
                'price_per_night' => 140,
                'available_rooms' => 2,
                'rating' => 4.6,
                'source' => 'supplier_b'
            ]
        ]);
    }

    public function supplierC(): JsonResponse
    {
        return response()->json([
            [
                'name' => 'Luxor Palace',
                'location' => 'Luxor, Egypt',
                'price_per_night' => 180,
                'available_rooms' => 8,
                'rating' => 4.7,
                'source' => 'supplier_c'
            ],
            [
                'name' => 'Valley Resort',
                'location' => 'Luxor, Egypt',
                'price_per_night' => 160,
                'available_rooms' => 4,
                'rating' => 4.4,
                'source' => 'supplier_c'
            ],
            [
                'name' => 'Kings Hotel',
                'location' => 'Luxor, Egypt',
                'price_per_night' => 135,
                'available_rooms' => 3,
                'rating' => 4.2,
                'source' => 'supplier_c'
            ]
        ]);
    }

    public function supplierD(): JsonResponse
    {
        return response()->json([
            [
                'name' => 'Red Sea Resort',
                'location' => 'Sharm El Sheikh, Egypt',
                'price_per_night' => 200,
                'available_rooms' => 10,
                'rating' => 4.9,
                'source' => 'supplier_d'
            ],
            [
                'name' => 'Coral Bay Hotel',
                'location' => 'Sharm El Sheikh, Egypt',
                'price_per_night' => 175,
                'available_rooms' => 6,
                'rating' => 4.5,
                'source' => 'supplier_d'
            ],
            [
                'name' => 'Ocean View Resort',
                'location' => 'Sharm El Sheikh, Egypt',
                'price_per_night' => 190,
                'available_rooms' => 4,
                'rating' => 4.6,
                'source' => 'supplier_d'
            ]
        ]);
    }
}
