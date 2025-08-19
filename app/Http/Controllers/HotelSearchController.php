<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelSearchRequest;
use Illuminate\Http\JsonResponse;

class HotelSearchController extends Controller
{
    public function search(HotelSearchRequest $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
