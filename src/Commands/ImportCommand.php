<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Commands;

use Illuminate\Console\Command;
use WeBRTeu\Emag\EmagImporter;
use WeBRTeu\Emag\Exceptions\EmagException;
use WeBRTeu\Emag\Models\EmagProduct;

class ImportCommand extends Command
{
    protected $signature = 'emag:import
        {--page=1 : Pagina de la care începe importul}
        {--no-images : Nu descarcă imaginile (păstrează doar URL-urile eMAG)}';

    protected $description = 'Importă ofertele eMAG Marketplace: produse, caracteristici, variante și imagini';

    public function handle(EmagImporter $importer): int
    {
        $page = $this->option('page');

        try {
            $count = $importer->importAll(
                withImages: ! $this->option('no-images'),
                startPage: is_numeric($page) ? (int) $page : 1,
                onImported: function (EmagProduct $product): void {
                    $this->line(sprintf('  %d · %s', $product->emag_id, $product->name), verbosity: 'v');
                },
            );
        } catch (EmagException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Produse importate: {$count}.");

        return self::SUCCESS;
    }
}
