<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etrn_roulette_spins', function (Blueprint $table): void {
            $table->string('result_kind', 16)->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
        });

        DB::table('etrn_roulette_spins')->orderBy('id')->chunkById(500, function ($spins): void {
            foreach ($spins as $spin) {
                $outcome = json_decode((string) $spin->outcome, true);
                $kind = $spin->is_jackpot
                    ? 'jackpot'
                    : (($outcome['matched'] ?? false) ? 'match' : 'miss');

                DB::table('etrn_roulette_spins')->where('id', $spin->id)->update(['result_kind' => $kind]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('etrn_roulette_spins', function (Blueprint $table): void {
            $table->dropColumn(['result_kind', 'ip_address', 'user_agent']);
        });
    }
};
