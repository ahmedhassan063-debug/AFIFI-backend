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
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id', 'orders_user_id_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'order_items_order_id_index');
        });

        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->index(
                ['product_variant_id', 'status', 'expires_at'],
                'stock_reservations_variant_status_expires_index'
            );
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['order_id', 'status'], 'payments_order_id_status_index');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->index(['order_id', 'status'], 'refunds_order_id_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_id_index');
        });

        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->dropIndex('stock_reservations_variant_status_expires_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_order_id_status_index');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex('refunds_order_id_status_index');
        });
    }
};
