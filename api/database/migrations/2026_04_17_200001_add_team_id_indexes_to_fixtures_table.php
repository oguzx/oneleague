<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->index('home_team_id', 'fixtures_home_team_id_idx');
            $table->index('away_team_id', 'fixtures_away_team_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropIndex('fixtures_home_team_id_idx');
            $table->dropIndex('fixtures_away_team_id_idx');
        });
    }
};
