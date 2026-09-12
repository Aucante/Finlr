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
        Schema::table('two_factor_trusted_devices', function (Blueprint $table) {
            // Additive and nullable: rows issued before this column existed
            // keep a null label, which the frontend renders with a generic
            // translated fallback rather than an empty cell.
            $table->string('label')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('two_factor_trusted_devices', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
