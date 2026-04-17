<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->uuid('tournament_id')->nullable()->after('id');
            $table->foreign('tournament_id')->references('id')->on('tournaments')->cascadeOnDelete();
        });

        DB::statement('
            UPDATE fixtures
            SET tournament_id = groups.tournament_id
            FROM groups
            WHERE fixtures.group_id = groups.id
        ');

        Schema::table('fixtures', function (Blueprint $table) {
            $table->uuid('tournament_id')->nullable(false)->change();
            $table->index(['tournament_id', 'match_week', 'status'], 'fixtures_tournament_week_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropIndex('fixtures_tournament_week_status_idx');
            $table->dropForeign(['tournament_id']);
            $table->dropColumn('tournament_id');
        });
    }
};
