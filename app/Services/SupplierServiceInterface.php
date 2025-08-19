<?php

namespace App\Services;

interface SupplierServiceInterface
{
    public function fetchHotels(array $filters): array;
}
