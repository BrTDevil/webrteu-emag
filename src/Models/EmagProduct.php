<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $emag_id
 * @property string|null $part_number
 * @property string|null $part_number_key
 * @property string $name
 * @property string|null $brand
 * @property string|null $description
 * @property int|null $category_id
 * @property int|null $family_id
 * @property string|null $family_name
 * @property int|null $family_type_id
 * @property float|null $sale_price
 * @property float|null $recommended_price
 * @property int|null $general_stock
 * @property int|null $status
 * @property string|null $url
 * @property list<string>|null $ean
 * @property array<array-key, mixed>|null $raw
 */
class EmagProduct extends Model
{
    protected $table = 'emag_products';

    protected $guarded = ['id'];

    /**
     * @return HasMany<EmagProductCharacteristic, $this>
     */
    public function characteristics(): HasMany
    {
        return $this->hasMany(EmagProductCharacteristic::class);
    }

    /**
     * @return HasMany<EmagProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(EmagProductImage::class)->orderBy('position');
    }

    /**
     * Toate produsele din aceeași familie eMAG (inclusiv acesta).
     *
     * @return HasMany<EmagProduct, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'family_id', 'family_id')->whereNotNull('family_id');
    }

    /**
     * Opțiunile variantei (caracteristicile din family_type), ex. ['Mărime' => 'XL', 'Culoare' => 'Roșu'].
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->characteristics as $characteristic) {
            if ($characteristic->is_option) {
                $options[$characteristic->name ?? (string) $characteristic->characteristic_id] = $characteristic->value;
            }
        }

        return $options;
    }

    protected function casts(): array
    {
        return [
            'emag_id' => 'integer',
            'category_id' => 'integer',
            'family_id' => 'integer',
            'family_type_id' => 'integer',
            'sale_price' => 'float',
            'recommended_price' => 'float',
            'general_stock' => 'integer',
            'status' => 'integer',
            'ean' => 'array',
            'raw' => 'array',
        ];
    }
}
