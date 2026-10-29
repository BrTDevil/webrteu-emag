<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Data;

use WeBRTeu\Emag\Support\Value;

/**
 * Numele caracteristicilor dintr-o categorie și caracteristicile care diferă între variante (family_types).
 */
final readonly class CategoryData
{
    /**
     * @param  array<int, string>  $names  characteristic_id => nume
     * @param  array<int, list<int>>  $familyTypes  family_type_id => characteristic_id-urile variantelor
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public array $names = [],
        public array $familyTypes = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $row  un element din results la category/read
     */
    public static function fromArray(array $row): self
    {
        $names = [];

        foreach (Value::array($row, 'characteristics') as $characteristic) {
            if (is_array($characteristic) && ($id = Value::int($characteristic, 'id')) !== null && ($name = Value::string($characteristic, 'name')) !== null) {
                $names[$id] = $name;
            }
        }

        $familyTypes = [];

        foreach (Value::array($row, 'family_types') as $familyType) {
            if (! is_array($familyType) || ($typeId = Value::int($familyType, 'id')) === null) {
                continue;
            }

            $familyTypes[$typeId] = [];

            foreach (Value::array($familyType, 'characteristics') as $characteristic) {
                if (is_array($characteristic) && ($id = Value::int($characteristic, 'characteristic_id')) !== null) {
                    $familyTypes[$typeId][] = $id;
                }
            }
        }

        return new self(Value::int($row, 'id'), Value::string($row, 'name'), $names, $familyTypes);
    }

    public function characteristicName(int $id): ?string
    {
        return $this->names[$id] ?? null;
    }

    public function isOption(?int $familyTypeId, int $characteristicId): bool
    {
        return $familyTypeId !== null && in_array($characteristicId, $this->familyTypes[$familyTypeId] ?? [], true);
    }
}
