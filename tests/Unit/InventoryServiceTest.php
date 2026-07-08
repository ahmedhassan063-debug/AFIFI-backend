<?php

namespace Tests\Unit;

use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Services\InventoryService;
use RuntimeException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InventoryService();
    }

    public function test_hold_reservation_creates_reservation_without_reducing_physical_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);

        $reservation = $this->service->holdReservation($variant, 3, now()->addMinutes(15));

        $this->assertSame('pending', $reservation->status);
        $this->assertSame(3, $reservation->quantity);
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_confirm_reservation_reduces_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);
        $reservation = $this->service->holdReservation($variant, 3, now()->addMinutes(15));

        $confirmed = $this->service->confirmReservation($reservation);

        $this->assertSame('confirmed', $confirmed->status);
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_release_reservation_releases_hold_without_changing_physical_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);
        $reservation = $this->service->holdReservation($variant, 3, now()->addMinutes(15));

        $released = $this->service->releaseReservation($reservation);

        $this->assertSame('released', $released->status);
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_insufficient_stock_blocks_reservation(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 2]);

        try {
            $this->service->holdReservation($variant, 5, now()->addMinutes(15));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Insufficient available stock.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('stock_reservations', ['product_variant_id' => $variant->id]);
        $this->assertSame(2, $variant->fresh()->stock);
    }

    public function test_calculate_available_stock_subtracts_active_reservations(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);
        $this->service->holdReservation($variant, 4, now()->addMinutes(15));

        $available = $this->service->calculateAvailableStock($variant);

        $this->assertSame(6, $available);
    }

    public function test_calculate_available_stock_ignores_expired_reservations(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);

        StockReservation::factory()->for($variant, 'productVariant')->create([
            'quantity' => 4,
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);

        $available = $this->service->calculateAvailableStock($variant);

        $this->assertSame(10, $available);
    }
}
