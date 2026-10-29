<?php

declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use WeBRTeu\Emag\Data\CharacteristicData;
use WeBRTeu\Emag\Data\ImageData;
use WeBRTeu\Emag\Data\ProductData;
use WeBRTeu\Emag\EmagImporter;
use WeBRTeu\Emag\Events\EmagProductImported;
use WeBRTeu\Emag\Models\EmagProduct;
use WeBRTeu\Emag\Models\EmagProductImage;

const IMG = 'https://s13emagst.akamaized.net/products/';

beforeEach(function (): void {
    Storage::fake('public');
    Http::fake([
        EMAG.'product_offer/read' => Http::response(fixtureJson('product-offer-read.json')),
        EMAG.'category/read' => Http::response(fixtureJson('category-read.json')),
        IMG.'*' => fn (): PromiseInterface => config('testing.image_fails') ? Http::response('', 404) : Http::response('octeti-imagine'),
    ]);
});

it('importă produsele cu caracteristici, opțiuni, variante și imagini', function (): void {
    Event::fake([EmagProductImported::class]);

    expect(app(EmagImporter::class)->importAll())->toBe(2);

    $product = EmagProduct::query()->where('emag_id', 1001)->firstOrFail();

    expect($product->name)->toBe('Tricou bumbac roșu M')
        ->and($product->sale_price)->toBe(49.99)
        ->and($product->ean)->toBe(['5940000000017'])
        ->and($product->raw)->toHaveKey('part_number', 'TRC-RED-M')
        ->and($product->options())->toBe(['Mărime' => 'M', 'Culoare' => 'Roșu'])
        ->and($product->characteristics()->where('characteristic_id', 8100)->value('name'))->toBe('Material')
        ->and($product->variants()->pluck('emag_id')->all())->toBe([1001, 1002]);

    $images = $product->images;

    expect($images)->toHaveCount(2)
        ->and($images[0]->display_type)->toBe(1)
        ->and($images[0]->path)->toBe('emag/1001/0-fata.jpg')
        ->and($images[1]->path)->toBe('emag/1001/1-spate.jpg')
        ->and($images[0]->product->is($product))->toBeTrue()
        ->and($images[0]->src())->toContain('emag/1001/0-fata.jpg');

    Storage::disk('public')->assertExists(['emag/1001/0-fata.jpg', 'emag/1002/0-fata.webp']);
    expect(Storage::disk('public')->get('emag/1001/0-fata.jpg'))->toBe('octeti-imagine');
    Event::assertDispatchedTimes(EmagProductImported::class, 2);
    Http::assertSentCount(1 + 1 + 3);
});

it('reimportul nu redescarcă imaginile și șterge ce a dispărut', function (): void {
    $importer = app(EmagImporter::class);
    $importer->import(ProductData::fromArray(firstOffer()));

    $offer = firstOffer();
    $offer['images'] = [['display_type' => 1, 'url' => IMG.'1/fata.jpg']];
    $offer['characteristics'] = [['id' => 8100, 'value' => 'In']];
    $product = $importer->import(ProductData::fromArray($offer));

    expect(EmagProduct::query()->count())->toBe(1)
        ->and($product->images()->count())->toBe(1)
        ->and($product->characteristics()->pluck('value')->all())->toBe(['In'])
        ->and($product->options())->toBe([]);
    Storage::disk('public')->assertMissing('emag/1001/1-spate.jpg');
    Http::assertSentCount(1 + 2);
});

it('păstrează URL-ul eMAG când descărcarea imaginii eșuează', function (): void {
    Log::spy();
    config()->set('testing.image_fails', true);

    $product = app(EmagImporter::class)->import(ProductData::fromArray(firstOffer()));
    $image = $product->images()->firstOrFail();

    expect($image->path)->toBeNull()
        ->and($image->src())->toBe(IMG.'1/fata.jpg');
    Log::shouldHaveReceived('warning')->with('emag.image_failed', Mockery::type('array'))->twice();
});

it('nu descarcă imaginile fără opțiune sau cu download dezactivat', function (bool $withImages, bool $download): void {
    config()->set('emag.images.download', $download);

    $product = app(EmagImporter::class)->import(ProductData::fromArray(firstOffer()), $withImages);

    expect($product->images()->whereNull('path')->count())->toBe(2);
    Storage::disk('public')->assertDirectoryEmpty('/');
})->with([[false, true], [true, false]]);

it('importă fără categorie și cu extensie necunoscută; numele lipsă devine id-ul', function (): void {
    $product = app(EmagImporter::class)->import(new ProductData(
        id: 9,
        name: 'Fără categorie',
        familyTypeId: 9,
        images: [new ImageData(IMG.'9/---.bin')],
        characteristics: [new CharacteristicData(5, 'x')],
    ));

    expect($product->images()->value('path'))->toBe('emag/9/0-imagine.jpg')
        ->and($product->characteristics()->value('name'))->toBeNull()
        ->and($product->options())->toBe([]);

    $product->characteristics()->update(['is_option' => true]);

    expect($product->fresh()?->options())->toBe(['5' => 'x'])
        ->and(EmagProductImage::query()->firstOrFail()->product->is($product))->toBeTrue()
        ->and($product->characteristics()->firstOrFail()->product->is($product))->toBeTrue();
    Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), 'category/read'));
});
