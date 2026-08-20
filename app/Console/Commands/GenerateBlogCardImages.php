<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\BlogCardImageGenerator;
use Illuminate\Console\Command;
use Throwable;

class GenerateBlogCardImages extends Command
{
    protected $signature = 'blog-cards:generate
        {--slug= : Сгенерировать миниатюру только для указанной статьи}
        {--force : Перегенерировать уже подготовленные миниатюры}
        {--show-logo : Наложить логотип сайта поверх карточки}';

    protected $description = 'Создаёт миниатюры статей 1200×675 с размытым фоном без обрезания исходного изображения';

    public function handle(BlogCardImageGenerator $generator): int
    {
        $query = BlogPost::query()
            ->whereNotNull('cover_image_path')
            ->where('cover_image_path', '!=', '')
            ->when($this->option('slug'), fn ($query, string $slug) => $query->where('slug', $slug))
            ->unless($this->option('force'), fn ($query) => $query->whereNull('card_image_path'))
            ->orderBy('id');

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->info('Нет статей для генерации.');

            return self::SUCCESS;
        }

        $errors = [];
        $this->withProgressBar($posts, function (BlogPost $post) use ($generator, &$errors): void {
            try {
                $generator->generate($post, (bool) $this->option('show-logo'));
            } catch (Throwable $exception) {
                $errors[] = $post->slug.': '.$exception->getMessage();
            }
        });
        $this->newLine(2);

        foreach ($errors as $error) {
            $this->error($error);
        }

        $generated = $posts->count() - count($errors);
        $this->info("Создано миниатюр: {$generated}.");

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
