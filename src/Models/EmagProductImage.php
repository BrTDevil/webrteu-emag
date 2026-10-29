<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $emag_product_id
 * @property string $url
 * @property int $display_type
 * @property int $position
 * @property string|null $path calea pe discul emag.images.disk, null dacă nu s-a descărcat
 */
class EmagProductImage extends Model
{
    protected $table = 'emag_product_images';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<EmagProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(EmagProduct::class, 'emag_product_id');
    }

    /**
     * URL-ul local dacă imaginea e descărcată, altfel cel de pe eMAG.
     */
    public function src(): string
    {
        return $this->path !== null ? Storage::disk(Config::string('emag.images.disk', 'public'))->url($this->path) : $this->url;
    }

    protected function casts(): array
    {
        return [
            'display_type' => 'integer',
            'position' => 'integer',
        ];
    }
}
