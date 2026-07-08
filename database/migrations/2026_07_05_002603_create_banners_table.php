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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 50)->default('hero')->index();
            $table->string('title', 200)->nullable();
            $table->string('subtitle', 300)->nullable();
            $table->foreignId('desktop_media_id')->constrained('media')->restrictOnDelete();
            $table->foreignId('mobile_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('button_text', 80)->nullable();
            $table->string('button_link', 500)->nullable();
            $table->unsignedInteger('priority')->default(0)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['placement', 'is_active']);
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
