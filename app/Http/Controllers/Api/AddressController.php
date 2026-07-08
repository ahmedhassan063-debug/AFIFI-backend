<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $addresses = auth()->user()
            ->addresses()
            ->with('governorate')
            ->latest()
            ->get();

        return AddressResource::collection($addresses);
    }

    public function show(Address $address): AddressResource
    {
        $this->ensureAddressBelongsToUser($address);
        $this->authorize('view', $address);

        return new AddressResource($address->load('governorate'));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        abort_unless((int) $data['user_id'] === auth()->user()->id, 403);

        $address = DB::transaction(function () use ($data) {
            if (($data['is_default'] ?? false) === true) {
                $this->clearDefaultAddresses();
            }

            return Address::query()->create($data);
        });

        return (new AddressResource($address->load('governorate')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAddressRequest $request, Address $address): AddressResource
    {
        $this->ensureAddressBelongsToUser($address);
        $this->authorize('update', $address);
        $data = $request->validated();

        if (isset($data['user_id'])) {
            abort_unless((int) $data['user_id'] === auth()->user()->id, 403);
        }

        $address = DB::transaction(function () use ($address, $data) {
            if (($data['is_default'] ?? false) === true) {
                $this->clearDefaultAddresses();
            }

            $address->update($data);

            return $address->refresh();
        });

        return new AddressResource($address->load('governorate'));
    }

    public function destroy(Address $address): JsonResponse
    {
        $this->ensureAddressBelongsToUser($address);
        $this->authorize('delete', $address);
        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.',
        ]);
    }

    public function setDefault(Address $address): AddressResource
    {
        $this->ensureAddressBelongsToUser($address);
        $this->authorize('setDefault', $address);

        $address = DB::transaction(function () use ($address) {
            $this->clearDefaultAddresses();
            $address->update(['is_default' => true]);

            return $address->refresh();
        });

        return new AddressResource($address->load('governorate'));
    }

    private function clearDefaultAddresses(): void
    {
        auth()->user()
            ->addresses()
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function ensureAddressBelongsToUser(Address $address): void
    {
        abort_unless($address->user_id === auth()->user()->id, 404);
    }
}
