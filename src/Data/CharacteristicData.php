<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Data;

final readonly class CharacteristicData
{
    public function __construct(
        public int $id,
        public string $value,
        public ?string $tag = null,
    ) {}
}
