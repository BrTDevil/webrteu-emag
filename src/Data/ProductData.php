<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Data;

use WeBRTeu\Emag\Support\Value;

/**
 * O ofertă din product_offer/read. ⚠️ Câmpurile se confirmă pe un răspuns real al contului.
 */
final readonly class ProductData
{
    /**
     * @param  list<string>  $ean
     * @param  list<ImageData>  $images
     * @param  list<CharacteristicData>  $characteristics
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $partNumber = null,
        public ?string $partNumberKey = null,
        public ?string $brand = null,
        public ?string $description = null,
        public ?int $categoryId = null,
        public ?int $familyId = null,
        public ?string $familyName = null,
        public ?int $familyTypeId = null,
        public ?float $salePrice = null,
        public ?float $recommendedPrice = null,
        public ?int $generalStock = null,
        public ?int $status = null,
        public ?string $url = null,
        public array $ean = [],
        public array $images = [],
        public array $characteristics = [],
        public array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $family = Value::array($row, 'family');

        return new self(
            id: (int) Value::int($row, 'id'),
            name: Value::string($row, 'name') ?? '',
            partNumber: Value::string($row, 'part_number'),
            partNumberKey: Value::string($row, 'part_number_key'),
            brand: Value::string($row, 'brand'),
            description: Value::string($row, 'description'),
            categoryId: Value::int($row, 'category_id'),
            familyId: ($familyId = Value::int($family, 'id')) !== 0 ? $familyId : null,
            familyName: Value::string($family, 'name'),
            familyTypeId: Value::int($family, 'family_type_id'),
            salePrice: Value::float($row, 'sale_price'),
            recommendedPrice: Value::float($row, 'recommended_price'),
            generalStock: Value::int($row, 'general_stock'),
            status: Value::int($row, 'status'),
            url: Value::string($row, 'url'),
            ean: self::ean($row),
            images: self::images($row),
            characteristics: self::characteristics($row),
            raw: $row,
        );
    }

    public function mainImage(): ?ImageData
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        return $this->images[0] ?? null;
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return list<string>
     */
    private static function ean(array $row): array
    {
        $ean = [];

        foreach (Value::array($row, 'ean') as $code) {
            if (is_scalar($code) && (string) $code !== '') {
                $ean[] = (string) $code;
            }
        }

        return $ean;
    }

    /**
     * Imaginea principală prima, apoi restul în ordinea primită; fără duplicate.
     *
     * @param  array<array-key, mixed>  $row
     * @return list<ImageData>
     */
    private static function images(array $row): array
    {
        $images = [];

        foreach (Value::array($row, 'images') as $image) {
            if (is_array($image) && ($url = Value::string($image, 'url')) !== null && ! isset($images[$url])) {
                $images[$url] = new ImageData($url, Value::int($image, 'display_type') ?? 0);
            }
        }

        usort($images, fn (ImageData $a, ImageData $b): int => (int) $b->isMain() <=> (int) $a->isMain());

        return $images;
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return list<CharacteristicData>
     */
    private static function characteristics(array $row): array
    {
        $characteristics = [];

        foreach (Value::array($row, 'characteristics') as $characteristic) {
            if (is_array($characteristic) && ($id = Value::int($characteristic, 'id')) !== null && ($value = Value::string($characteristic, 'value')) !== null) {
                $characteristics[] = new CharacteristicData($id, $value, Value::string($characteristic, 'tag'));
            }
        }

        return $characteristics;
    }
}
