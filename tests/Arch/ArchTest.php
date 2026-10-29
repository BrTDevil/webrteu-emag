<?php

declare(strict_types=1);

arch('nu folosește funcții de debug')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();

arch('toate fișierele au strict types')
    ->expect('WeBRTeu\Emag')
    ->toUseStrictTypes();

arch('fără Guzzle direct: HTTP doar prin Http::')
    ->expect('WeBRTeu\Emag')
    ->not->toUse('GuzzleHttp');

arch('DTO-urile sunt readonly')
    ->expect('WeBRTeu\Emag\Data')
    ->toBeReadonly();

arch('excepțiile extind RuntimeException')
    ->expect('WeBRTeu\Emag\Exceptions')
    ->toExtend(RuntimeException::class);
