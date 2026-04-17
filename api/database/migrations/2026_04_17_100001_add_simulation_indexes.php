<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->index(['group_id', 'match_week', 'status'], 'fixtures_group_week_status_idx');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->index('tournament_id', 'groups_tournament_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropIndex('fixtures_group_week_status_idx');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex('groups_tournament_id_idx');
        });
    }
};
