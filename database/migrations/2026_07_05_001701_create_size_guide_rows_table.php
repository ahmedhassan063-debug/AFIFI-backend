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
        Schema::create('size_guide_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('size_guide_id')->constrained('size_guides')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->enum('measurement_type', ['chest', 'length', 'shoulder', 'waist', 'hip', 'inseam']);
            $table->decimal('value_cm', 6, 2);
            $table->timestamps();

            $table->unique(['size_guide_id', 'size_id', 'measurement_type']);
            $table->index('size_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('size_guide_rows');
    }
};
