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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->decimal('fee', 10, 2);
            $table->unsignedTinyInteger('estimated_days_min');
            $table->unsignedTinyInteger('estimated_days_max');
            $table->boolean('is_active')->default(true)->index();
            $table->date('valid_from')->nullable()->index();
            $table->date('valid_until')->nullable()->index();
            $table->timestamps();

            $table->index(['shipping_zone_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
