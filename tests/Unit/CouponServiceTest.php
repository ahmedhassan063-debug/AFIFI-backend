<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Services\CouponService;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CouponService();
    }

    public function test_discount_cannot_exceed_order_subtotal(): void
    {
        $coupon = Coupon::factory()->create([
            'type' => 'fixed',
            'value' => 250,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $discount = $this->service->calculateDiscount($coupon, 200.0);

        $this->assertSame(200.0, $discount);
    }
}
