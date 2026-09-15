<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the old composite unique index to support this foreign key.
        // Give the foreign key its own index before replacing that unique index.
        if (! Schema::hasIndex('community_reactions', 'community_reaction_user_fk_index')) {
            Schema::table('community_reactions', function (Blueprint $table): void {
                $table->index('community_user_id', 'community_reaction_user_fk_index');
            });
        }

        if (Schema::hasIndex('community_reactions', 'community_reaction_one_per_target')) {
            Schema::table('community_reactions', function (Blueprint $table): void {
                $table->dropUnique('community_reaction_one_per_target');
            });
        }

        if (! Schema::hasIndex('community_reactions', 'community_reaction_one_per_code')) {
            Schema::table('community_reactions', function (Blueprint $table): void {
                $table->unique(
                    ['community_user_id', 'target_type', 'target_id', 'code'],
                    'community_reaction_one_per_code'
                );
            });
        }
    }

    public function down(): void
    {
        $duplicateIds = DB::table('community_reactions')
            ->orderBy('id')
            ->get(['id', 'community_user_id', 'target_type', 'target_id'])
            ->groupBy(fn ($reaction): string => implode(':', [
                $reaction->community_user_id,
                $reaction->target_type,
                $reaction->target_id,
            ]))
            ->flatMap(fn ($reactions) => $reactions->skip(1)->pluck('id'));

        if ($duplicateIds->isNotEmpty()) {
            DB::table('community_reactions')->whereIn('id', $duplicateIds)->delete();
        }

        if (! Schema::hasIndex('community_reactions', 'community_reaction_one_per_target')) {
            Schema::table('community_reactions', function (Blueprint $table): void {
                $table->unique(
                    ['community_user_id', 'target_type', 'target_id'],
                    'community_reaction_one_per_target'
                );
            });
        }

        if (Schema::hasIndex('community_reactions', 'community_reaction_one_per_code')) {
            Schema::table('community_reactions', function (Blueprint $table): void {
                $table->dropUnique('community_reaction_one_per_code');
            });
        }

        if (Schema::hasIndex('community_reactions', 'community_reaction_user_fk_index')) {
            Schema::table('community_reactions', function (Blueprint $table): void {
                $table->dropIndex('community_reaction_user_fk_index');
            });
        }
    }
};
