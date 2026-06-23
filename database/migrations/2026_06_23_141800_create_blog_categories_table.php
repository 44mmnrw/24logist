<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->foreignId('blog_category_id')
                ->nullable()
                ->after('category')
                ->constrained('blog_categories')
                ->nullOnDelete();
        });

        $this->migrateLegacyCategories();
        $this->ensureDefaultNewsCategory();
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('blog_category_id');
        });

        Schema::dropIfExists('blog_categories');
    }

    private function migrateLegacyCategories(): void
    {
        $categories = DB::table('blog_posts')
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn (string $category): string => trim($category))
            ->filter()
            ->unique()
            ->values();

        $usedSlugs = [];

        foreach ($categories as $index => $name) {
            $slug = $this->uniqueSlug($name, $usedSlugs, $index + 1);
            $usedSlugs[] = $slug;

            $categoryId = DB::table('blog_categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('blog_posts')
                ->whereRaw('TRIM(category) = ?', [$name])
                ->update(['blog_category_id' => $categoryId]);
        }
    }

    private function ensureDefaultNewsCategory(): void
    {
        $exists = DB::table('blog_categories')
            ->where('slug', 'news')
            ->orWhere('name', 'Новости')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('blog_categories')->insert([
            'name' => 'Новости',
            'slug' => 'news',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $usedSlugs
     */
    private function uniqueSlug(string $name, array $usedSlugs, int $fallbackIndex): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'category-'.$fallbackIndex;
        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $usedSlugs, true)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
};
