<?php

declare(strict_types=1);

const EMAG = 'https://marketplace-api.emag.ro/api-3/';

function fixtureFile(string $name): string
{
    return (string) file_get_contents(__DIR__.'/Fixtures/'.$name);
}

/**
 * @return array<array-key, mixed>
 */
function fixtureJson(string $name): array
{
    return (array) json_decode(fixtureFile($name), true);
}

/**
 * @return array<array-key, mixed>
 */
function firstOffer(): array
{
    return (array) fixtureJson('product-offer-read.json')['results'][0];
}
