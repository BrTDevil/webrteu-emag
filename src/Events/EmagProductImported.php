<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Events;

use Illuminate\Foundation\Events\Dispatchable;
use WeBRTeu\Emag\Data\ProductData;
use WeBRTeu\Emag\Models\EmagProduct;

/**
 * Punctul de legătură cu catalogul aplicației: ascultă evenimentul și mapează în modelele proprii.
 */
class EmagProductImported
{
    use Dispatchable;

    public function __construct(
        public readonly EmagProduct $product,
        public readonly ProductData $data,
    ) {}
}
