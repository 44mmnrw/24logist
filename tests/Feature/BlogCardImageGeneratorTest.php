<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Services\BlogCardImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogCardImageGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_creates_exact_16_by_9_card_image_without_replacing_cover(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        Storage::fake('public');

        $coverPath = 'blog/covers/source.jpg';
        $source = imagecreatetruecolor(900, 600);
        $background = imagecolorallocate($source, 33, 93, 168);
        imagefilledrectangle($source, 0, 0, 899, 599, $background);
        Storage::disk('public')->makeDirectory('blog/covers');
        imagejpeg($source, Storage::disk('public')->path($coverPath), 90);
        imagedestroy($source);

        $post = BlogPost::query()->create([
            'title' => 'Карточка статьи',
            'slug' => 'card-image-test',
            'body' => 'Текст статьи',
            'cover_image_path' => $coverPath,
            'is_published' => true,
        ]);

        $generatedPath = app(BlogCardImageGenerator::class)->generate($post, false);
        $post->refresh();

        Storage::disk('public')->assertExists($generatedPath);
        $this->assertSame([1200, 675], array_slice(getimagesize(Storage::disk('public')->path($generatedPath)), 0, 2));
        $this->assertSame($coverPath, $post->cover_image_path);
        $this->assertSame($generatedPath, $post->card_image_path);
        $this->assertFalse($post->show_card_logo);
        $this->assertTrue($post->hasPreparedCardImage());
        $this->assertFalse($post->shouldShowCardLogo());
    }

    public function test_card_image_falls_back_to_cover_and_logo_requires_prepared_image(): void
    {
        $post = new BlogPost([
            'cover_image_path' => 'blog/covers/cover.jpg',
            'show_card_logo' => true,
        ]);

        $this->assertSame($post->coverImageUrl(), $post->cardImageUrl());
        $this->assertFalse($post->hasPreparedCardImage());
        $this->assertFalse($post->shouldShowCardLogo());

        $post->card_image_path = 'blog/cards/card.webp';

        $this->assertTrue($post->hasPreparedCardImage());
        $this->assertTrue($post->shouldShowCardLogo());
        $this->assertSame('blog/cards/card.webp', $post->articleImagePath());

        $post->show_card_logo = false;

        $this->assertSame('blog/covers/cover.jpg', $post->articleImagePath());
    }

    public function test_logo_is_baked_into_generated_card_and_position_changes_the_file(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        Storage::fake('public');

        $coverPath = 'blog/covers/logo-source.jpg';
        $source = imagecreatetruecolor(1200, 675);
        $background = imagecolorallocate($source, 240, 240, 240);
        imagefilledrectangle($source, 0, 0, 1199, 674, $background);
        Storage::disk('public')->makeDirectory('blog/covers');
        imagejpeg($source, Storage::disk('public')->path($coverPath), 90);
        imagedestroy($source);

        $post = BlogPost::query()->create([
            'title' => 'Branded card',
            'slug' => 'branded-card',
            'body' => 'Article body',
            'cover_image_path' => $coverPath,
            'show_card_logo' => true,
            'card_logo_position' => 'top-left',
        ]);

        $firstPath = app(BlogCardImageGenerator::class)->generate($post, true);
        $firstHash = hash_file('sha256', Storage::disk('public')->path($firstPath));

        $post->forceFill(['card_logo_position' => 'bottom-right'])->saveQuietly();
        $secondPath = app(BlogCardImageGenerator::class)->generate($post, true);
        $secondHash = hash_file('sha256', Storage::disk('public')->path($secondPath));

        $this->assertNotSame($firstPath, $secondPath);
        $this->assertNotSame($firstHash, $secondHash);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }
}
