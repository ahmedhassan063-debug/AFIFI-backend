<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CouponService
{
    public function validateCoupon(Coupon|string $coupon, float $orderTotal): Coupon
    {
        $coupon = $this->resolveCoupon($coupon);

        if (! $coupon->is_active) {
            throw new RuntimeException('Coupon is not active.');
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            throw new RuntimeException('Coupon is not active yet.');
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            throw new RuntimeException('Coupon has expired.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new RuntimeException('Coupon usage limit has been reached.');
        }

        if ($coupon->min_order_total !== null && $orderTotal < (float) $coupon->min_order_total) {
            throw new RuntimeException('Order total does not meet the coupon minimum.');
        }

        return $coupon;
    }

    public function calculateDiscount(Coupon|string $coupon, float $orderTotal): float
    {
        $coupon = $this->validateCoupon($coupon, $orderTotal);

        $discount = match ($coupon->type) {
            'percent' => $orderTotal * ((float) $coupon->value / 100),
            'fixed' => (float) $coupon->value,
            default => 0.0,
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(min($discount, $orderTotal), 2);
    }

    public function incrementUsedCount(Coupon|int|string $coupon): Coupon
    {
        return DB::transaction(function () use ($coupon) {
            $coupon = $this->lockCoupon($coupon);

            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                throw new RuntimeException('Coupon usage limit has been reached.');
            }

            $coupon->increment('used_count');

            return $coupon->refresh();
        });
    }

    public function recordRedemption(
        Coupon|int|string $coupon,
        int $orderId,
        float $discountAmount,
        ?int $userId = null,
        bool $incrementUsage = true
    ): CouponRedemption {
        return DB::transaction(function () use ($coupon, $orderId, $discountAmount, $userId, $incrementUsage) {
            $coupon = $this->lockCoupon($coupon);

            $redemption = CouponRedemption::query()->create([
                'coupon_id' => $coupon->id,
                'order_id' => $orderId,
                'user_id' => $userId,
                'discount_amount' => $discountAmount,
            ]);

            if ($incrementUsage) {
                if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                    throw new RuntimeException('Coupon usage limit has been reached.');
                }

                $coupon->increment('used_count');
            }

            return $redemption;
        });
    }

    private function resolveCoupon(Coupon|string $coupon): Coupon
    {
        if ($coupon instanceof Coupon) {
            return $coupon;
        }

        return Coupon::query()
            ->where('code', $coupon)
            ->firstOrFail();
    }

    private function lockCoupon(Coupon|int|string $coupon): Coupon
    {
        if ($coupon instanceof Coupon) {
            $coupon = $coupon->id;
        }

        return Coupon::query()
            ->when(is_numeric($coupon), fn ($query) => $query->whereKey($coupon))
            ->when(! is_numeric($coupon), fn ($query) => $query->where('code', $coupon))
            ->lockForUpdate()
            ->firstOrFail();
    }
}
