<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function adjustStock(
        ProductVariant|int $productVariant,
        int $quantityDelta,
        ?string $notes = null,
        ?int $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $productVariant,
            $quantityDelta,
            $notes,
            $createdBy,
            $referenceType,
            $referenceId
        ) {
            $variant = $this->lockVariant($productVariant);
            $stockAfter = $variant->stock + $quantityDelta;

            if ($stockAfter < 0) {
                throw new RuntimeException('Stock cannot be negative.');
            }

            $variant->update(['stock' => $stockAfter]);

            return $this->recordMovement(
                $variant,
                'adjustment',
                $quantityDelta,
                $stockAfter,
                $referenceType,
                $referenceId,
                $notes,
                $createdBy
            );
        });
    }

    public function restock(
        ProductVariant|int $productVariant,
        int $quantity,
        ?string $notes = null,
        ?int $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new RuntimeException('Restock quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productVariant,
            $quantity,
            $notes,
            $createdBy,
            $referenceType,
            $referenceId
        ) {
            $variant = $this->lockVariant($productVariant);
            $stockAfter = $variant->stock + $quantity;

            $variant->update(['stock' => $stockAfter]);

            return $this->recordMovement(
                $variant,
                'restock',
                $quantity,
                $stockAfter,
                $referenceType,
                $referenceId,
                $notes,
                $createdBy
            );
        });
    }

    public function recordMovement(
        ProductVariant|int $productVariant,
        string $type,
        int $quantityDelta,
        int $stockAfter,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): InventoryMovement {
        $variant = $this->resolveVariant($productVariant);

        return InventoryMovement::query()->create([
            'product_variant_id' => $variant->id,
            'type' => $type,
            'quantity_delta' => $quantityDelta,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    public function holdReservation(
        ProductVariant|int $productVariant,
        int $quantity,
        mixed $expiresAt,
        ?int $userId = null,
        ?string $sessionId = null,
        ?string $notes = null
    ): StockReservation {
        if ($quantity <= 0) {
            throw new RuntimeException('Reservation quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($productVariant, $quantity, $expiresAt, $userId, $sessionId, $notes) {
            $variant = $this->lockVariant($productVariant);

            if ($this->calculateAvailableStock($variant) < $quantity) {
                throw new RuntimeException('Insufficient available stock.');
            }

            $reservation = StockReservation::query()->create([
                'product_variant_id' => $variant->id,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'quantity' => $quantity,
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'notes' => $notes,
            ]);

            $this->recordMovement(
                $variant,
                'reservation_hold',
                -$quantity,
                $variant->stock,
                StockReservation::class,
                $reservation->id,
                $notes,
            );

            return $reservation;
        });
    }

    public function releaseReservation(StockReservation|int $reservation, ?string $notes = null): StockReservation
    {
        return DB::transaction(function () use ($reservation, $notes) {
            $reservation = $this->lockReservation($reservation);

            if ($reservation->status !== 'pending') {
                return $reservation;
            }

            $reservation->update([
                'status' => 'released',
                'notes' => $notes ?? $reservation->notes,
            ]);

            $this->recordMovement(
                $reservation->product_variant_id,
                'reservation_release',
                $reservation->quantity,
                $reservation->productVariant()->value('stock'),
                StockReservation::class,
                $reservation->id,
                $notes,
            );

            return $reservation->refresh();
        });
    }

    public function confirmReservation(StockReservation|int $reservation, ?string $notes = null): StockReservation
    {
        return DB::transaction(function () use ($reservation, $notes) {
            $reservation = $this->lockReservation($reservation);

            if ($reservation->status !== 'pending') {
                return $reservation;
            }

            $variant = $this->lockVariant($reservation->product_variant_id);
            $stockAfter = $variant->stock - $reservation->quantity;

            if ($stockAfter < 0) {
                throw new RuntimeException('Stock cannot be negative.');
            }

            $variant->update(['stock' => $stockAfter]);

            $reservation->update([
                'status' => 'confirmed',
                'notes' => $notes ?? $reservation->notes,
            ]);

            $this->recordMovement(
                $variant,
                'sale',
                -$reservation->quantity,
                $stockAfter,
                StockReservation::class,
                $reservation->id,
                $notes,
            );

            return $reservation->refresh();
        });
    }

    public function calculateAvailableStock(ProductVariant|int $productVariant): int
    {
        $variant = $this->resolveVariant($productVariant);
        $reservedQuantity = StockReservation::query()
            ->where('product_variant_id', $variant->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->sum('quantity');

        return max(0, $variant->stock - $reservedQuantity);
    }

    private function resolveVariant(ProductVariant|int $productVariant): ProductVariant
    {
        if ($productVariant instanceof ProductVariant) {
            return $productVariant;
        }

        return ProductVariant::query()->findOrFail($productVariant);
    }

    private function lockVariant(ProductVariant|int $productVariant): ProductVariant
    {
        $variantId = $productVariant instanceof ProductVariant ? $productVariant->id : $productVariant;

        return ProductVariant::query()
            ->whereKey($variantId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockReservation(StockReservation|int $reservation): StockReservation
    {
        $reservationId = $reservation instanceof StockReservation ? $reservation->id : $reservation;

        return StockReservation::query()
            ->whereKey($reservationId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
