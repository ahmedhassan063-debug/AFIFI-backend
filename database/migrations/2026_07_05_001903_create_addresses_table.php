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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 50)->nullable();
            $table->string('full_name', 150);
            $table->string('phone', 20)->index();
            $table->foreignId('governorate_id')->constrained('governorates')->restrictOnDelete();
            $table->string('city', 100);
            $table->string('area', 150)->nullable();
            $table->string('street', 200);
            $table->string('building', 50)->nullable();
            $table->string('floor', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
