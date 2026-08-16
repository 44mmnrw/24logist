<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_monitoring_settings', function (Blueprint $table): void {
            $table->id();
            $table->text('yandex_api_key')->nullable();
            $table->string('yandex_folder_id')->nullable();
            $table->string('target_host')->default('24logist.ru');
            $table->string('default_region_id', 100)->default('225');
            $table->string('default_device', 32)->default('DEVICE_ALL');
            $table->unsignedSmallInteger('position_depth')->default(100);
            $table->unsignedSmallInteger('position_batch_limit')->default(5);
            $table->unsignedSmallInteger('wordstat_limit')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_monitoring_settings');
    }
};
