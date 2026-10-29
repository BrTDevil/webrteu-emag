<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use WeBRTeu\Emag\Models\EmagProductImage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('rulează importul complet', function (): void {
    Http::fake([
        EMAG.'product_offer/read' => Http::response(fixtureJson('product-offer-read.json')),
        EMAG.'category/read' => Http::response(fixtureJson('category-read.json')),
        'https://s13emagst.akamaized.net/*' => Http::response('img'),
    ]);

    $this->artisan('emag:import', ['-v' => true])
        ->expectsOutputToContain('1001 · Tricou bumbac roșu M')
        ->expectsOutput('Produse importate: 2.')
        ->assertSuccessful();

    expect(EmagProductImage::query()->whereNotNull('path')->count())->toBe(3);
});

it('respectă --page și --no-images', function (): void {
    Http::fake([
        EMAG.'product_offer/read' => Http::response(fixtureJson('product-offer-read.json')),
        EMAG.'category/read' => Http::response(fixtureJson('category-read.json')),
    ]);

    $this->artisan('emag:import', ['--page' => '4', '--no-images' => true])->assertSuccessful();

    expect(EmagProductImage::query()->whereNotNull('path')->count())->toBe(0);
    Http::assertSent(fn (Request $request): bool => ($request->data()['currentPage'] ?? null) === 4);
});

it('pagina invalidă pornește de la 1', function (): void {
    Http::fake([EMAG.'product_offer/read' => Http::response(['isError' => false, 'results' => []])]);

    $this->artisan('emag:import', ['--page' => 'abc'])->expectsOutput('Produse importate: 0.')->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->data()['currentPage'] === 1);
});

it('afișează eroarea eMAG și iese cu cod de eșec', function (): void {
    Http::fake([EMAG.'*' => Http::response(['isError' => true, 'messages' => ['IP neautorizat']])]);

    $this->artisan('emag:import')->expectsOutputToContain('IP neautorizat')->assertFailed();
});
