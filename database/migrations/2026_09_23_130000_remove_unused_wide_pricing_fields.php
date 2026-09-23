<?php

use App\Models\LandingBlock;
use App\Services\LandingPageService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->first();

        if (! $plan) {
            return;
        }

        $extra = is_array($plan->extra) ? $plan->extra : [];
        foreach ([
            'workplace_one',
            'workplace_few',
            'workplace_many',
            'period_label',
            'month_label',
            'year_label',
            'users_decrease_label',
            'users_increase_label',
        ] as $key) {
            unset($extra[$key]);
        }

        $plan->update([
            'description' => $plan->description === 'за {users} {workplaces}'
                ? 'за одного пользователя в месяц'
                : $plan->description,
            'extra' => $extra,
        ]);

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        // Removed content should not replace later admin edits.
    }
};
