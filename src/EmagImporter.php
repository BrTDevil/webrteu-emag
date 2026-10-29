<?php

declare(strict_types=1);

namespace WeBRTeu\Emag;

use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use WeBRTeu\Emag\Data\CategoryData;
use WeBRTeu\Emag\Data\ImageData;
use WeBRTeu\Emag\Data\ProductData;
use WeBRTeu\Emag\Events\EmagProductImported;
use WeBRTeu\Emag\Models\EmagProduct;
use WeBRTeu\Emag\Models\EmagProductImage;

/**
 * Salvează ofertele eMAG local: produs, caracteristici (cu nume și marcaj de opțiune) și imagini.
 * Reimportul e idempotent: imaginile deja descărcate nu se mai descarcă, cele dispărute se șterg.
 */
class EmagImporter
{
    public function __construct(private readonly EmagCatalog $catalog) {}

    /**
     * @param  (Closure(EmagProduct): void)|null  $onImported
     */
    public function importAll(bool $withImages = true, int $startPage = 1, ?Closure $onImported = null): int
    {
        $count = 0;

        foreach ($this->catalog->products($startPage) as $data) {
            $product = $this->import($data, $withImages);
            $count++;

            if ($onImported instanceof Closure) {
                $onImported($product);
            }
        }

        return $count;
    }

    public function import(ProductData $data, bool $withImages = true): EmagProduct
    {
        $category = $data->categoryId !== null ? $this->catalog->category($data->categoryId) : new CategoryData;

        $product = DB::transaction(fn (): EmagProduct => $this->save($data, $category));

        $this->syncImages($product, $data, $withImages && Config::boolean('emag.images.download', true));

        EmagProductImported::dispatch($product, $data);

        return $product;
    }

    private function save(ProductData $data, CategoryData $category): EmagProduct
    {
        $product = EmagProduct::query()->updateOrCreate(['emag_id' => $data->id], [
            'part_number' => $data->partNumber,
            'part_number_key' => $data->partNumberKey,
            'name' => $data->name,
            'brand' => $data->brand,
            'description' => $data->description,
            'category_id' => $data->categoryId,
            'family_id' => $data->familyId,
            'family_name' => $data->familyName,
            'family_type_id' => $data->familyTypeId,
            'sale_price' => $data->salePrice,
            'recommended_price' => $data->recommendedPrice,
            'general_stock' => $data->generalStock,
            'status' => $data->status,
            'url' => $data->url,
            'ean' => $data->ean,
            'raw' => $data->raw,
        ]);

        $product->characteristics()->delete();

        foreach ($data->characteristics as $characteristic) {
            $product->characteristics()->create([
                'characteristic_id' => $characteristic->id,
                'name' => $category->characteristicName($characteristic->id),
                'value' => $characteristic->value,
                'tag' => $characteristic->tag,
                'is_option' => $category->isOption($data->familyTypeId, $characteristic->id),
            ]);
        }

        return $product;
    }

    private function syncImages(EmagProduct $product, ProductData $data, bool $download): void
    {
        $existing = $product->images()->get()->keyBy('url');
        $keep = [];

        foreach ($data->images as $position => $image) {
            $model = $existing->get($image->url) ?? new EmagProductImage(['url' => $image->url]);
            $model->display_type = $image->displayType;
            $model->position = $position;

            if ($download && $model->path === null) {
                $model->path = $this->download($product, $image, $position);
            }

            $product->images()->save($model);
            $keep[] = $model->id;
        }

        foreach ($product->images()->whereNotIn('id', $keep)->get() as $old) {
            if ($old->path !== null) {
                $this->disk()->delete($old->path);
            }

            $old->delete();
        }
    }

    private function download(EmagProduct $product, ImageData $image, int $position): ?string
    {
        try {
            $body = Http::timeout(Config::integer('emag.images.timeout', 30))->get($image->url)->throw()->body();
        } catch (Throwable $exception) {
            // Imaginea rămâne cu URL-ul eMAG; se reîncearcă la următorul import.
            Log::warning('emag.image_failed', ['emag_id' => $product->emag_id, 'url' => $image->url, 'error' => $exception->getMessage()]);

            return null;
        }

        $file = (string) parse_url($image->url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $path = sprintf(
            '%s/%d/%d-%s.%s',
            trim(Config::string('emag.images.path', 'emag'), '/'),
            $product->emag_id,
            $position,
            Str::slug(pathinfo($file, PATHINFO_FILENAME)) ?: 'imagine',
            in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg',
        );

        $this->disk()->put($path, $body);

        return $path;
    }

    private function disk(): Filesystem
    {
        return Storage::disk(Config::string('emag.images.disk', 'public'));
    }
}
