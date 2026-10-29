<?php

declare(strict_types=1);

namespace WeBRTeu\Emag;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use WeBRTeu\Emag\Exceptions\EmagException;

/**
 * HTTP brut spre eMAG Marketplace API v3: Basic Auth, POST JSON pe {resource}/{action}.
 * Erorile vin și la HTTP 200, cu isError = true și lista messages.
 */
class EmagClient
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed> câmpul results
     */
    public function call(string $resource, string $action, array $data = []): array
    {
        $username = Config::string('emag.username', '');
        $password = Config::string('emag.password', '');

        if ($username === '' || $password === '') {
            throw EmagException::missingCredentials();
        }

        Sleep::for(Config::integer('emag.throttle_ms', 350))->milliseconds();

        $endpoint = $resource.'/'.$action;
        $response = Http::timeout(Config::integer('emag.timeout', 30))
            ->withBasicAuth($username, $password)
            ->acceptJson()
            ->asJson()
            ->post(rtrim(Config::string('emag.base_url', 'https://marketplace-api.emag.ro/api-3'), '/').'/'.$endpoint, $data);

        $json = $response->json();

        if ($response->failed() || ! is_array($json) || ($json['isError'] ?? false) === true) {
            $messages = is_array($json) ? Support\Value::array($json, 'messages') : [];

            Log::warning('emag.request_failed', ['endpoint' => $endpoint, 'status' => $response->status(), 'messages' => $messages]);

            throw EmagException::fromMessages($endpoint, $response->status(), $messages);
        }

        return Support\Value::array($json, 'results');
    }
}
