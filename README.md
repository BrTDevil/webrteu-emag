# webrteu/emag

Importă ofertele din **eMAG Marketplace API v3** în Laravel 12–13: produse, caracteristici (cu nume), opțiuni de variantă (mărime, culoare etc.), familii și imagini descărcate local.

## Instalare

```bash
composer require webrteu/emag
php artisan vendor:publish --tag=emag-config
php artisan vendor:publish --tag=emag-migrations
php artisan migrate
```

```dotenv
EMAG_BASE_URL=https://marketplace-api.emag.ro/api-3   # .bg / .hu pentru celelalte platforme
EMAG_USERNAME=utilizator-api
EMAG_PASSWORD=parola-api
EMAG_IMAGES_DISK=public
EMAG_IMAGES_PATH=emag
```

> eMAG acceptă cereri doar de la IP-uri din whitelist: adaugă IP-ul serverului în contul de vânzător.

## Import

```bash
php artisan emag:import              # tot catalogul, cu imagini
php artisan emag:import --no-images  # doar URL-urile imaginilor
php artisan emag:import --page=5 -v  # reia de la pagina 5 și afișează fiecare produs
```

Din cod:

```php
use WeBRTeu\Emag\EmagImporter;

$count = app(EmagImporter::class)->importAll();
```

Reimportul e idempotent: produsul se actualizează după `emag_id`, caracteristicile se rescriu, imaginile deja descărcate nu se mai descarcă, iar cele scoase din eMAG se șterg (și fișierele lor).

## Ce se salvează

| Tabel | Conținut |
|---|---|
| `emag_products` | id eMAG, part_number, PNK, nume, brand, descriere, categorie, familie, prețuri, stoc, EAN, răspunsul brut (`raw`) |
| `emag_product_characteristics` | id caracteristică, nume (din `category/read`), valoare, tag, `is_option` |
| `emag_product_images` | URL eMAG, tip (1 = principală), poziție, cale locală |

```php
$product = EmagProduct::where('part_number', 'TRC-RED-M')->first();

$product->options();          // ['Mărime' => 'M', 'Culoare' => 'Roșu']
$product->variants;           // toată familia (alte mărimi/culori)
$product->images->first()->src(); // URL local, sau cel eMAG dacă nu s-a descărcat
```

## Legarea de catalogul tău

Ascultă evenimentul și mapează în modelele aplicației:

```php
use WeBRTeu\Emag\Events\EmagProductImported;

Event::listen(function (EmagProductImported $event): void {
    Product::updateOrCreate(['sku' => $event->product->part_number], [
        'name' => $event->product->name,
        'price' => $event->product->sale_price,
    ]);
});
```

## Teste

```bash
composer test
```

⚠️ Fixture-urile sunt scrise după documentație. Înainte de v1.0 trebuie înlocuite cu răspunsuri reale.

## Licență

MIT · [WeBRTeu](https://webrt.eu)
