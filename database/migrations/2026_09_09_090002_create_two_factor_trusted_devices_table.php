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
        Schema::create('two_factor_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Selector/validator split (CONCEPTION.md, section 1) : the
            // `two_factor_trusted` cookie carries `selector|validator` in
            // clear, only the validator is hashed here — a leak of this
            // table alone can't be used to forge a valid cookie.
            $table->string('selector')->unique();
            $table->string('hashed_validator');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_trusted_devices');
    }
};
