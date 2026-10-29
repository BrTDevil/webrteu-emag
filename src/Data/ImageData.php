<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Data;

final readonly class ImageData
{
    public const MAIN = 1;

    public const SECONDARY = 2;

    public function __construct(
        public string $url,
        public int $displayType = 0,
    ) {}

    public function isMain(): bool
    {
        return $this->displayType === self::MAIN;
    }
}
