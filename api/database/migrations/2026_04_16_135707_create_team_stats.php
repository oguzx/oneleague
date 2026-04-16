<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_stats', function (Blueprint $table) {
            $table->uuid('team_id')->primary();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();

            // Core attributes
            $table->unsignedTinyInteger('attack');
            $table->unsignedTinyInteger('midfield');
            $table->unsignedTinyInteger('defense');
            $table->unsignedTinyInteger('speed');
            $table->unsignedTinyInteger('pass');
            $table->unsignedTinyInteger('shot');
            $table->unsignedTinyInteger('goalkeeper');
            $table->unsignedTinyInteger('finishing');
            $table->unsignedTinyInteger('chance_creation');
            $table->unsignedTinyInteger('pressing');
            $table->unsignedTinyInteger('set_piece_strength');

            // Mental / tactical
            $table->unsignedTinyInteger('winner_mentality');
            $table->unsignedTinyInteger('loser_mentality');
            $table->unsignedTinyInteger('consistency');
            $table->unsignedTinyInteger('discipline');
            $table->unsignedTinyInteger('fatigue_resistance');
            $table->unsignedTinyInteger('big_match_performance');
            $table->unsignedTinyInteger('resilience');
            $table->unsignedTinyInteger('manager_influence');

            // Squad info
            $table->unsignedTinyInteger('squad_depth');
            $table->unsignedTinyInteger('injury_risk');
            $table->unsignedTinyInteger('star_players_count');
            $table->unsignedTinyInteger('pot');

            // Environmental
            $table->unsignedTinyInteger('home_advantage');
            $table->tinyInteger('min_temp_performance');
            $table->tinyInteger('max_temp_performance');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_stats');
    }
};
