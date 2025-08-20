<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelSearchRequest;
use App\Services\HotelAggregatorService;
use Illuminate\Http\JsonResponse;

class HotelSearchController extends Controller
{
    public function __construct(
        private HotelAggregatorService $hotelAggregator
    ) {}

    public function search(HotelSearchRequest $request): JsonResponse
    {
        $results = $this->hotelAggregator->searchHotels($request->validated());
        
        return response()->json($results);
    }
}
