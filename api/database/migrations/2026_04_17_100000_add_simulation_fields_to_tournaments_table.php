<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('simulation_status', 20)->default('idle')->after('name');
            $table->string('simulation_batch_id', 255)->nullable()->after('simulation_status');
            $table->timestamp('simulation_started_at')->nullable()->after('simulation_batch_id');
            $table->timestamp('simulation_finished_at')->nullable()->after('simulation_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'simulation_status',
                'simulation_batch_id',
                'simulation_started_at',
                'simulation_finished_at',
            ]);
        });
    }
};
