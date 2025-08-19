<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

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

    public function supplierA()
    {
        $hotels = $this->loadJsonFile('supplier-a.json');
        return response()->json($hotels);
    }

    public function supplierB()
    {
        $hotels = $this->loadJsonFile('supplier-b.json');
        return response()->json($hotels);
    }

    public function supplierC()
    {
        $hotels = $this->loadJsonFile('supplier-c.json');
        return response()->json($hotels);
    }

    public function supplierD()
    {
        $hotels = $this->loadJsonFile('supplier-d.json');
        return response()->json($hotels); 
    }
}
