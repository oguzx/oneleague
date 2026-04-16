<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fixture_id');
            $table->foreign('fixture_id')->references('id')->on('fixtures')->cascadeOnDelete();
            $table->unsignedTinyInteger('minute');
            $table->unsignedTinyInteger('second');
            $table->unsignedSmallInteger('tick_number');
            $table->unsignedTinyInteger('sequence')->default(0);
            $table->uuid('team_id')->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
            $table->uuid('opponent_team_id')->nullable();
            $table->foreign('opponent_team_id')->references('id')->on('teams')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('zone', 30)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['fixture_id', 'minute']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
