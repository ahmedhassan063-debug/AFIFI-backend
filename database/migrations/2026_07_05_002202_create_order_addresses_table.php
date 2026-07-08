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
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('type', ['shipping', 'billing'])->default('shipping');
            $table->string('full_name', 150);
            $table->string('phone', 20);
            $table->string('governorate_name', 100);
            $table->string('shipping_zone_code', 30);
            $table->string('city', 100);
            $table->string('area', 150)->nullable();
            $table->string('street', 200);
            $table->string('building', 50)->nullable();
            $table->string('floor', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['order_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
