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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 20)->nullable()->index();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->decimal('exchange_rate', 16, 8);
            $table->string('status', 30)->default('pending_confirmation')->index();
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->string('payment_method', 30)->index();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->timestamps();

            $table->index('currency_id');
            $table->index('coupon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
