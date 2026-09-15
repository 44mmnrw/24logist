<?php

namespace Database\Seeders;

use App\Models\CommunityAiSource;
use Illuminate\Database\Seeder;

class CommunityAiSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Чат логистов 1', 'chat_id' => '-77817133750861'],
            ['name' => 'Чат логистов 2', 'chat_id' => '-78472905945311'],
            ['name' => 'Чат логистов 3', 'chat_id' => '-76919674091617'],
        ] as $source) {
            CommunityAiSource::query()->updateOrCreate(
                ['platform' => 'max', 'external_chat_id' => $source['chat_id']],
                [
                    'name' => $source['name'],
                    'public_url' => 'https://web.max.ru/'.$source['chat_id'],
                    'is_active' => true,
                ],
            );
        }
    }
}
