<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use WeBRTeu\Emag\EmagClient;
use WeBRTeu\Emag\Exceptions\EmagException;

it('trimite POST JSON cu Basic Auth și întoarce results', function (): void {
    Http::fake([EMAG.'vat/read' => Http::response(['isError' => false, 'results' => [['vat_id' => 1]]])]);

    expect(app(EmagClient::class)->call('vat', 'read', ['a' => 1]))->toBe([['vat_id' => 1]]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === EMAG.'vat/read'
        && $request->header('Authorization')[0] === 'Basic '.base64_encode('seller@example.com:secret-pass')
        && $request->data() === ['a' => 1]);
    Sleep::assertSleptTimes(1);
});

it('întoarce listă goală când results lipsește', function (): void {
    Http::fake([EMAG.'*' => Http::response(['isError' => false])]);

    expect(app(EmagClient::class)->call('vat', 'read'))->toBe([]);
});

it('aruncă la isError chiar cu HTTP 200 și loghează mesajele', function (): void {
    Log::spy();
    Http::fake([EMAG.'*' => Http::response(['isError' => true, 'messages' => ['IP neautorizat', ['text' => 'Alt mesaj'], 3]])]);

    expect(fn (): array => app(EmagClient::class)->call('product_offer', 'read'))
        ->toThrow(EmagException::class, 'eMAG product_offer/read a eșuat (HTTP 200): IP neautorizat; Alt mesaj');

    Log::shouldHaveReceived('warning')->once();
});

it('aruncă la HTTP 500 fără JSON', function (): void {
    Http::fake([EMAG.'*' => Http::response('Server Error', 500)]);

    expect(fn (): array => app(EmagClient::class)->call('vat', 'read'))
        ->toThrow(EmagException::class, '(HTTP 500): fără mesaj');
});

it('cere credențiale', function (): void {
    config()->set('emag.password', '');
    Http::fake();

    expect(fn (): array => app(EmagClient::class)->call('vat', 'read'))->toThrow(EmagException::class, 'EMAG_USERNAME');

    Http::assertNothingSent();
});
