<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $emag_product_id
 * @property int $characteristic_id
 * @property string|null $name
 * @property string $value
 * @property string|null $tag
 * @property bool $is_option
 */
class EmagProductCharacteristic extends Model
{
    protected $table = 'emag_product_characteristics';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<EmagProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(EmagProduct::class, 'emag_product_id');
    }

    protected function casts(): array
    {
        return [
            'characteristic_id' => 'integer',
            'is_option' => 'boolean',
        ];
    }
}
