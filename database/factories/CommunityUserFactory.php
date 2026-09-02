<?php

namespace Database\Factories;

use App\Models\CommunityUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityUser> */
class CommunityUserFactory extends Factory
{
    protected $model = CommunityUser::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'display_name' => fn (array $attributes): string => $attributes['username'],
            'role' => 'user',
            'onboarded_at' => now(),
            'terms_accepted_at' => now(),
        ];
    }
}
