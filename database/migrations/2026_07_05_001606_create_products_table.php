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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->enum('gender', ['men', 'women', 'unisex'])->nullable()->index();
            $table->enum('badge', ['new', 'hot'])->nullable()->index();
            $table->decimal('base_price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->boolean('has_variants')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_new_arrival')->default(false)->index();
            $table->boolean('is_best_seller')->default(false)->index();
            $table->boolean('is_featured_drop')->default(false)->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index(['category_id', 'is_active']);
            $table->index(['brand_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
