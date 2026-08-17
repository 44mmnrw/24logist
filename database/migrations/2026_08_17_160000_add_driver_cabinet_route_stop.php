<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('landing_sections')
            ->where('slug', 'driver_cabinet')
            ->where(function ($query): void {
                $query->whereNull('route_enabled')
                    ->orWhere(function ($query): void {
                        $query->where('route_enabled', false)
                            ->whereNull('route_label');
                    });
            })
            ->update([
                'route_enabled' => true,
                'route_label' => 'ЛК водителя',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('landing_sections')
            ->where('slug', 'driver_cabinet')
            ->where('route_label', 'ЛК водителя')
            ->update([
                'route_enabled' => false,
                'route_label' => null,
                'updated_at' => now(),
            ]);
    }
};
