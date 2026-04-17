<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->boolean('is_manually_edited')->default(false)->after('simulation_seed');
            $table->timestamp('manually_edited_at')->nullable()->after('is_manually_edited');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropColumn(['is_manually_edited', 'manually_edited_at']);
        });
    }
};
