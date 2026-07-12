<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReturnRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Currency::factory()->create([
            'code' => 'EGP',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function createOrderWithItem(User $user, string $status = 'delivered'): array
    {
        $order = Order::factory()->for($user)->create([
            'status' => $status,
        ]);
        $item = OrderItem::factory()->for($order)->create();

        return [$order, $item];
    }

    /**
     * @return array<string, mixed>
     */
    private function returnPayload(Order $order, OrderItem $item, array $overrides = []): array
    {
        return array_merge([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Wrong size received.',
        ], $overrides);
    }

    public function test_guest_cannot_create_return_request(): void
    {
        $user = User::factory()->create();
        [$order, $item] = $this->createOrderWithItem($user);

        $this->postJson('/api/returns', $this->returnPayload($order, $item))
            ->assertUnauthorized();
    }

    public function test_customer_can_create_return_for_delivered_order_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        $response = $this->postJson('/api/returns', $this->returnPayload($order, $item));

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.type', 'return');
        $response->assertJsonPath('data.reason', 'Wrong size received.');
        $response->assertJsonPath('data.order_id', $order->id);
        $response->assertJsonPath('data.order_item_id', $item->id);
        $response->assertJsonMissingPath('data.admin_notes');
        $this->assertNotNull($response->json('data.requested_at'));

        $this->assertDatabaseHas('return_requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'status' => 'pending',
            'type' => 'return',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function nonDeliveredOrderStatusesProvider(): array
    {
        return [
            'pending_confirmation' => ['pending_confirmation'],
            'confirmed' => ['confirmed'],
            'processing' => ['processing'],
            'shipped' => ['shipped'],
            'cancelled' => ['cancelled'],
            'returned' => ['returned'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonDeliveredOrderStatusesProvider')]
    public function test_customer_cannot_create_return_for_non_delivered_order(string $status): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user, $status);

        $this->postJson('/api/returns', $this->returnPayload($order, $item))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Returns can only be requested for delivered orders.');
    }

    public function test_customer_cannot_create_return_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$order, $item] = $this->createOrderWithItem($owner);

        Sanctum::actingAs($other);

        $this->postJson('/api/returns', $this->returnPayload($order, $item))
            ->assertNotFound();
    }

    public function test_customer_cannot_use_order_item_from_different_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        [$orderA] = $this->createOrderWithItem($user);
        [, $itemB] = $this->createOrderWithItem($user);

        $this->postJson('/api/returns', $this->returnPayload($orderA, $itemB))
            ->assertForbidden();
    }

    public function test_duplicate_pending_return_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        $this->postJson('/api/returns', $this->returnPayload($order, $item))->assertCreated();

        $this->postJson('/api/returns', $this->returnPayload($order, $item, ['reason' => 'Second attempt.']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'A return request already exists for this item.');
    }

    public function test_duplicate_approved_return_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        $returnRequest = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Existing approved return.',
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        $this->postJson('/api/returns', $this->returnPayload($order, $item))
            ->assertStatus(422)
            ->assertJsonPath('message', 'A return request already exists for this item.');

        $this->assertSame($returnRequest->id, ReturnRequest::query()->count());
    }

    public function test_duplicate_completed_return_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Existing completed return.',
            'status' => 'completed',
            'requested_at' => now(),
            'resolved_at' => now(),
        ]);

        $this->postJson('/api/returns', $this->returnPayload($order, $item))
            ->assertStatus(422)
            ->assertJsonPath('message', 'A return request already exists for this item.');
    }

    public function test_new_request_after_rejected_return_is_allowed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Rejected earlier.',
            'status' => 'rejected',
            'requested_at' => now()->subDay(),
            'resolved_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/returns', $this->returnPayload($order, $item, [
            'reason' => 'Resubmitted after rejection.',
        ]));

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.reason', 'Resubmitted after rejection.');
        $this->assertSame(2, ReturnRequest::query()->where('order_item_id', $item->id)->count());
    }

    public function test_return_list_contains_only_authenticated_customers_returns(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        [$ownerOrder, $ownerItem] = $this->createOrderWithItem($owner);
        [$otherOrder, $otherItem] = $this->createOrderWithItem($other);

        ReturnRequest::query()->create([
            'order_id' => $ownerOrder->id,
            'order_item_id' => $ownerItem->id,
            'type' => 'return',
            'reason' => 'Owner return.',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        ReturnRequest::query()->create([
            'order_id' => $otherOrder->id,
            'order_item_id' => $otherItem->id,
            'type' => 'return',
            'reason' => 'Other return.',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/returns');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.order_id', $ownerOrder->id);
        $response->assertJsonMissingPath('data.0.admin_notes');
    }

    public function test_return_detail_for_another_customer_returns_not_found(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$order, $item] = $this->createOrderWithItem($owner);

        $returnRequest = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Owner return.',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        Sanctum::actingAs($other);

        $this->getJson("/api/returns/{$returnRequest->id}")
            ->assertNotFound();
    }

    public function test_customer_return_detail_does_not_include_admin_notes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        $returnRequest = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Customer reason.',
            'status' => 'pending',
            'admin_notes' => 'Internal review note.',
            'requested_at' => now(),
        ]);

        $this->getJson("/api/returns/{$returnRequest->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.admin_notes');
    }

    public function test_embedded_order_return_requests_do_not_expose_admin_notes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Customer reason.',
            'status' => 'pending',
            'admin_notes' => 'Internal review note.',
            'requested_at' => now(),
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonPath('data.return_requests.0.reason', 'Customer reason.');
        $response->assertJsonMissingPath('data.return_requests.0.admin_notes');
    }

    public function test_authorized_admin_can_update_return_status(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        [$order, $item] = $this->createOrderWithItem($user);

        $returnRequest = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Customer reason.',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/admin/returns/{$returnRequest->id}/status", [
            'status' => 'approved',
            'admin_notes' => 'Approved after inspection.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');
        $response->assertJsonPath('data.admin_notes', 'Approved after inspection.');
        $this->assertNotNull($returnRequest->fresh()->resolved_at);
    }

    public function test_customer_cannot_update_return_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        [$order, $item] = $this->createOrderWithItem($user);

        $returnRequest = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'return',
            'reason' => 'Customer reason.',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->patchJson("/api/admin/returns/{$returnRequest->id}/status", [
            'status' => 'approved',
        ])->assertForbidden();

        $this->assertSame('pending', $returnRequest->fresh()->status);
    }
}
