<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Emag\EmagCatalog;

it('paginează ofertele până la o pagină incompletă', function (): void {
    config()->set('emag.items_per_page', 2);
    Http::fake([EMAG.'product_offer/read' => Http::sequence()
        ->push(fixtureJson('product-offer-read.json'))
        ->push(['isError' => false, 'results' => [['id' => 1003, 'name' => 'Al treilea'], ['fără id']]])
        ->push(['isError' => false, 'results' => ['x']]),
    ]);

    $ids = app(EmagCatalog::class)->products()->map(fn ($product): int => $product->id)->all();

    expect($ids)->toBe([1001, 1002, 1003]);
    Http::assertSent(fn (Request $request): bool => $request->data() === ['currentPage' => 3, 'itemsPerPage' => 2]);
    Http::assertSentCount(3);
});

it('pornește de la pagina cerută', function (): void {
    Http::fake([EMAG.'product_offer/read' => Http::response(['isError' => false, 'results' => []])]);

    expect(app(EmagCatalog::class)->products(3)->all())->toBe([]);
    Http::assertSent(fn (Request $request): bool => $request->data()['currentPage'] === 3);
});

it('numără ofertele', function (): void {
    Http::fake([EMAG.'product_offer/count' => Http::response(['isError' => false, 'results' => ['noOfItems' => '250', 'noOfPages' => 3]])]);

    expect(app(EmagCatalog::class)->count())->toBe(250);
});

it('ține categoriile în cache pe durata rulării', function (): void {
    Http::fake([EMAG.'category/read' => Http::response(fixtureJson('category-read.json'))]);
    $catalog = app(EmagCatalog::class);

    expect($catalog->category(506)->characteristicName(7000))->toBe('Culoare')
        ->and($catalog->category(506)->name)->toBe('Tricouri');
    Http::assertSentCount(1);
});

it('continuă fără nume când categoria nu se poate citi', function (): void {
    Log::spy();
    Http::fake([EMAG.'category/read' => Http::sequence()
        ->push(['isError' => true, 'messages' => ['Categorie inexistentă']])
        ->push(['isError' => false, 'results' => ['x']]),
    ]);
    $catalog = app(EmagCatalog::class);

    expect($catalog->category(1)->id)->toBe(1)
        ->and($catalog->category(1)->names)->toBe([])
        ->and($catalog->category(2)->id)->toBeNull();
    Log::shouldHaveReceived('warning')->with('emag.category_failed', Mockery::type('array'))->once();
});
