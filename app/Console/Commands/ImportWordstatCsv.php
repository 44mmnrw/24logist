<?php

namespace App\Console\Commands;

use App\Services\Seo\WordstatCsvImporter;
use Illuminate\Console\Command;

class ImportWordstatCsv extends Command
{
    protected $signature = 'seo:import-wordstat {path : Absolute path or path relative to storage/app/private}';

    protected $description = 'Import a Yandex Wordstat CSV into SEO monitoring history';

    public function handle(WordstatCsvImporter $importer): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $path = storage_path('app/private/'.ltrim(str_replace('\\', '/', $path), '/'));
        }

        $run = $importer->import($path);

        $this->info("Imported {$run->processed_items} keywords. Run #{$run->getKey()}.");

        return self::SUCCESS;
    }
}
