<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File; 
use Illuminate\Http\Request;

class MockSupplierController extends Controller
{
    private function loadJsonFile(string $filename): array
    {
        $path = database_path("json/{$filename}");
        if (!File::exists($path)) {
            return [];
        }
        return json_decode(File::get($path), true) ?? [];
    }

    public function filter($request, $hotels) {
        return collect($hotels)->filter(function ($hotel) use ($request) {
            return (!isset($request->location) || str_contains(strtolower($hotel['location']), strtolower($request->input('location'))) ) &&
                   (!isset($request->rating) || $hotel['rating'] >= $request->rating) &&
                   (!isset($request->price_min) || $hotel['price'] >= $request->price_min) &&
                   (!isset($request->price_max) || $hotel['price'] <= $request->price_max);
        })->values()->all();
    }

    public function supplierA(Request $request)
    {
        $hotels = $this->loadJsonFile('supplier-a.json');
        return response()->json($this->filter($request, $hotels));
    }

    public function supplierB(Request $request)
    {
        $hotels = $this->loadJsonFile('supplier-b.json');
        return response()->json($this->filter($request, $hotels));
    }

    public function supplierC(Request $request)
    {
        $hotels = $this->loadJsonFile('supplier-c.json');
        return response()->json($this->filter($request, $hotels));
    }

    public function supplierD(Request $request)
    {
        $hotels = $this->loadJsonFile('supplier-d.json');
        return response()->json($this->filter($request, $hotels));
    }
}
