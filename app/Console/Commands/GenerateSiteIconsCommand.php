<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSiteIconsCommand extends Command
{
    protected $signature = 'icons:generate';

    protected $description = 'Generate all site icons from public/images/favicon.svg';

    public function handle(): int
    {
        return $this->call('icons:generate-apple-touch') === self::SUCCESS
            && $this->call('icons:generate-pwa') === self::SUCCESS
            ? self::SUCCESS
            : self::FAILURE;
    }
}
