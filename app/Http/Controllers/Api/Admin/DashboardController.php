<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    /**
     * Optional `from`/`to` (YYYY-MM-DD) query params scope the
     * activity-based metrics (orders, revenue, recent orders). Snapshot
     * metrics (users, active products, low stock, pending returns/messages)
     * always reflect current state - a date range doesn't make sense for
     * "how many are pending right now".
     */
    public function summary(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString())->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay() : null;

        $grossRevenue = (float) Payment::query()
            ->where('status', 'paid')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->sum('amount');

        $refundedAmount = (float) Refund::query()
            ->where('status', 'processed')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->sum('amount');

        $recentOrders = Order::query()
            ->with('user:id,name')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'grand_total' => $order->grand_total,
                'created_at' => $order->created_at,
                'customer_name' => optional($order->user)->name ?? $order->guest_email ?? $order->guest_phone,
            ])
            ->values();

        $lowStockVariants = ProductVariant::query()
            ->with('product:id,name,slug')
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->orderBy('stock')
            ->limit(10)
            ->get()
            ->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'product' => $variant->product ? [
                    'id' => $variant->product->id,
                    'name' => $variant->product->name,
                    'slug' => $variant->product->slug,
                ] : null,
            ])
            ->values();

        return response()->json([
            'users_count' => User::query()->count(),
            'active_products_count' => Product::query()->where('is_active', true)->count(),
            'low_stock_variants_count' => ProductVariant::query()->where('stock', '<=', self::LOW_STOCK_THRESHOLD)->count(),
            'low_stock_variants' => $lowStockVariants,
            'pending_orders_count' => Order::query()
                ->where('status', 'pending_confirmation')
                ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
                ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
                ->count(),
            'unpaid_orders_count' => Order::query()
                ->where('payment_status', 'unpaid')
                ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
                ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
                ->count(),
            'pending_returns_count' => ReturnRequest::query()->where('status', 'pending')->count(),
            'new_contact_messages_count' => ContactMessage::query()->where('status', 'new')->count(),
            'revenue' => [
                'gross' => round($grossRevenue, 2),
                'refunded' => round($refundedAmount, 2),
                'net' => round(max(0, $grossRevenue - $refundedAmount), 2),
            ],
            'recent_orders' => $recentOrders,
            'date_range' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }
}
