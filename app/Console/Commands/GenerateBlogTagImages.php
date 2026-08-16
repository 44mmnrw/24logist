<?php

namespace App\Console\Commands;

use App\Models\BlogTag;
use App\Services\BlogTagSocialImageGenerator;
use Illuminate\Console\Command;

class GenerateBlogTagImages extends Command
{
    protected $signature = 'blog-tags:generate-images {--slug= : Сгенерировать изображение только для одного slug}';

    protected $description = 'Создать фирменные SEO-изображения для тегов блога';

    public function handle(BlogTagSocialImageGenerator $generator): int
    {
        $query = BlogTag::query()->orderBy('id');

        if (filled($slug = $this->option('slug'))) {
            $query->where('slug', $slug);
        }

        $tags = $query->get();

        if ($tags->isEmpty()) {
            $this->warn('Подходящие теги не найдены.');

            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar($tags->count());

        foreach ($tags as $tag) {
            $generator->generate($tag);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Изображения созданы: '.$tags->count());

        return self::SUCCESS;
    }
}
