<?php

declare(strict_types=1);

namespace WeBRTeu\Emag;

use Generator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use WeBRTeu\Emag\Data\CategoryData;
use WeBRTeu\Emag\Data\ProductData;
use WeBRTeu\Emag\Exceptions\EmagException;

/**
 * Citirea ofertelor (paginat) și a categoriilor (nume caracteristici + variante), cu cache pe durata rulării.
 */
class EmagCatalog
{
    /** @var array<int, CategoryData> */
    private array $categories = [];

    public function __construct(private readonly EmagClient $client) {}

    /**
     * @return LazyCollection<int, ProductData>
     */
    public function products(int $startPage = 1): LazyCollection
    {
        return LazyCollection::make(fn (): Generator => $this->pages(max(1, $startPage)));
    }

    public function count(): int
    {
        return (int) Support\Value::int($this->client->call('product_offer', 'count'), 'noOfItems');
    }

    public function category(int $id): CategoryData
    {
        if (isset($this->categories[$id])) {
            return $this->categories[$id];
        }

        try {
            $row = $this->client->call('category', 'read', ['id' => $id])[0] ?? [];
            $category = CategoryData::fromArray(is_array($row) ? $row : []);
        } catch (EmagException $exception) {
            // Fără nume de caracteristici importul continuă: valorile rămân, doar numele lipsesc.
            Log::warning('emag.category_failed', ['category_id' => $id, 'error' => $exception->getMessage()]);
            $category = new CategoryData($id);
        }

        return $this->categories[$id] = $category;
    }

    /**
     * @return Generator<int, ProductData>
     */
    private function pages(int $page): Generator
    {
        $perPage = max(1, Config::integer('emag.items_per_page', 100));

        do {
            $rows = $this->client->call('product_offer', 'read', ['currentPage' => $page, 'itemsPerPage' => $perPage]);

            foreach ($rows as $row) {
                if (is_array($row) && Support\Value::int($row, 'id') !== null) {
                    yield ProductData::fromArray($row);
                }
            }

            $page++;
        } while (count($rows) >= $perPage);
    }
}
