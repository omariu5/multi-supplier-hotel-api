# Multi-Supplier Hotel Search API (Laravel)

> Fast, parallel, and production-minded hotel search that merges results from multiple suppliers, standardizes removes duplicates, and returns the best deals — with tests, mocks, and great performance.

---

## What this do

- Talks to **several suppliers in parallel**
- **Standardizes** data shape across suppliers
- **removes duplicates** (keeps **lowest price**)
- **Filters & sorts**
- **Survives failures** from unexpected errors from suppliers servers

---

## Features

- `GET /api/hotels/search`
  - Query params: `location` (req), `check_in` (req), `check_out` (req), `guests`, `min_price`, `max_price`, `sort_by=price|rating` (default `price`), `page` (default `1`), `per_page` (default `10`)
- Fetches **4 supplier endpoints in parallel**
- Normalized payload shape:
  ```json
  {
    "id": "hash",
    "name": "Hotel Name",
    "location": "City, Country",
    "price_per_night": 120.0,
    "available_rooms": 5,
    "rating": 4.5,
    "source": "supplier_a"
  }
  ```
- **Filter** by price range & guests (via `available_rooms`)
- **Sort** by `price` (asc) or `rating` (desc)
- **Fake supplier JSON** lives in `database/json/*.json`
- **Unit tests** included

---

## Architecture & Design

### Parallelism (real concurrency)

- Uses Laravel HTTP Client’s `Http::pool()` to send **all supplier requests concurrently**.
- **Tight timeouts** (`connectTimeout(1)`, `timeout(3)`) prevent one dead supplier from blocking the whole response.
- **Multiple workers** required (Octane/FrankenPHP) to avoid self-deadlock when calling local mock endpoints. otherwise you would not need that for a production use and external suppliers' urls.
- **Strategy Pattern** to handle each supplier with the proper strategy in a separate class.

### Standardization / Normalization

- Suppliers may return different shapes; we **normalize** each item to a common schema in `processResponse()`.
- Unique identity uses `md5(strtolower(name|location))` — pragmatic way to match the “same” hotel across suppliers.

### De-duplication Strategy

- After normalization, results are grouped by `id` and we **keep the cheapest** (`price_per_night`) entry.  
- The winning supplier name is returned in `source`.

### Filtering & Sorting

- Filters (price range, guests, location) applied after merging for simplicity and correctness.
- Sorting options:
  - `price` → ascending (default)
  - `rating` → descending

### Resilience & Logging

- Non-successful responses or connection errors are logged.
- Failed suppliers are **skipped**; the API still returns results from the others.

### Performance Notes

- **Run with multiple workers** (Octane/FrankenPHP) — non-negotiable if the app calls its own mock endpoints. otherwise, increase the number of workers on your web server and php-fpm.
- **Tight HTTP timeouts**.
- (Optional) Add **per-supplier caching** (short TTL) if you want even faster hot queries, however, this was ignored for accuracy and demonstrating PHP/Laravel asynchronous cabapilities.

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- **One of**:
  - **Laravel Octane** with (FrankenPHP) — **recommended**

> ⚠️ `php artisan serve` runs a single worker and will deadlock/time out when the app calls its **own** mock endpoints. Use Octane/FrankenPHP (or run mocks on a different port/process).

### 1) Clone & Install

```bash
git clone https://github.com/omariu5/multi-supplier-hotel-api
cd multi-supplier-hotel-api

composer install
cp .env.example .env
php artisan key:generate
```

Important `.env` entries:

```env
APP_URL=http://127.0.0.1:8000   # used by the service to call local mock suppliers
OCTANE_SERVER=frankenphp
```

### 2) Start with Multiple Workers

#### Option A — Laravel Octane (recommended)

```bash
php artisan octane:install
# Choose FrankenPHP when prompted

# Start with multiple workers:
php artisan octane:start --workers=4 --task-workers=4
# App will listen on http://127.0.0.1:8000 by default
```

## API

### Search Hotels

`GET /api/hotels/search`

**Example cURL**

```bash
curl -G "http://127.0.0.1:8000/api/hotels/search"   --data-urlencode "location=Cairo"   --data-urlencode "check_in=2025-09-01"   --data-urlencode "check_out=2025-09-05"   --data-urlencode "guests=2"   --data-urlencode "min_price=50"   --data-urlencode "max_price=300"   --data-urlencode "sort_by=price"   --data-urlencode "page=1"   --data-urlencode "per_page=10"
```

**Response**

```json
{
  "data": [
    {
      "id": "4e1a...hash",
      "name": "Nile View",
      "location": "Cairo, Egypt",
      "price_per_night": 120.0,
      "available_rooms": 4,
      "rating": 4.3,
      "source": "supplier_b"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 23,
    "total_pages": 3
  }
}
```

---

## Fake Suppliers & JSON Data

- Supplier endpoints:
  - `/api/mock/supplier-a`
  - `/api/mock/supplier-b`
  - `/api/mock/supplier-c`
  - `/api/mock/supplier-d`
- Backed by **fake JSON** files in: `database/json/*.json`  
  You can tweak prices/ratings/rooms there to simulate different scenarios. or replace them entirely with a real supplier.


---

## Testing

### PHPUnit

Run the full tests:

```bash
php artisan test
```

### Postman

A Postman collection is included:  
`./Multi-Supplier Hotel API.postman_collection.json`

Steps:

1. Import the collection into Postman.
2. Set the base URL to `http://127.0.0.1:8000`.
3. Use the **Search** request; tweak query parameters in json and inspect responses.

---

## Performance Playbook

- **Run with workers** (Octane/FrankenPHP).
- **Tight HTTP timeouts**:
  ```php
  Http::connectTimeout(1)->timeout(3);
  ```
- **Supplier-side filtering**: pass query params to mocks/real suppliers to reduce payload size.
- **Short-TTL caching** (optional): cache per-supplier results for popular queries.

---

## License

MIT
