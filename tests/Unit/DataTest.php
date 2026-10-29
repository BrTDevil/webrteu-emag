<?php

declare(strict_types=1);

use WeBRTeu\Emag\Data\CategoryData;
use WeBRTeu\Emag\Data\ProductData;

it('citește o ofertă completă', function (): void {
    $product = ProductData::fromArray(firstOffer());

    expect($product->id)->toBe(1001)
        ->and($product->name)->toBe('Tricou bumbac roșu M')
        ->and($product->partNumberKey)->toBe('D5ABC12BM')
        ->and($product->familyId)->toBe(77)
        ->and($product->familyTypeId)->toBe(9)
        ->and($product->salePrice)->toBe(49.99)
        ->and($product->ean)->toBe(['5940000000017'])
        ->and($product->images)->toHaveCount(2)
        ->and($product->images[0]->url)->toEndWith('fata.jpg')
        ->and($product->mainImage()?->url)->toEndWith('fata.jpg')
        ->and($product->characteristics)->toHaveCount(3)
        ->and($product->characteristics[0]->tag)->toBe('original');
});

it('tolerează o ofertă minimă sau cu câmpuri stricate', function (): void {
    $product = ProductData::fromArray([
        'id' => '5',
        'family' => ['id' => 0],
        'images' => ['x', ['url' => ''], ['url' => 'https://e/a.png']],
        'characteristics' => [['id' => 1], 'x'],
        'ean' => [['nested']],
    ]);

    expect($product->id)->toBe(5)
        ->and($product->name)->toBe('')
        ->and($product->familyId)->toBeNull()
        ->and($product->images)->toHaveCount(1)
        ->and($product->mainImage()?->isMain())->toBeFalse()
        ->and($product->characteristics)->toBe([])
        ->and($product->ean)->toBe([])
        ->and(ProductData::fromArray([])->mainImage())->toBeNull();
});

it('citește numele caracteristicilor și opțiunile variantelor', function (): void {
    $category = CategoryData::fromArray((array) fixtureJson('category-read.json')['results'][0]);

    expect($category->id)->toBe(506)
        ->and($category->name)->toBe('Tricouri')
        ->and($category->characteristicName(6506))->toBe('Mărime')
        ->and($category->characteristicName(1))->toBeNull()
        ->and($category->isOption(9, 6506))->toBeTrue()
        ->and($category->isOption(9, 8100))->toBeFalse()
        ->and($category->isOption(null, 6506))->toBeFalse()
        ->and($category->isOption(404, 6506))->toBeFalse();
});
