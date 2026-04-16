<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->uuid('home_team_id');
            $table->foreign('home_team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->uuid('away_team_id');
            $table->foreign('away_team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('match_week');
            $table->string('status', 20)->default('scheduled');
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'home_team_id', 'away_team_id']);
            $table->index('match_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
