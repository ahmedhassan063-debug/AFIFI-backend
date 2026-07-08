<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminPreferenceRequest;
use App\Http\Requests\UpdateAdminPreferenceRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\AdminPreferenceResource;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::query()
            ->when($request->filled('group'), fn ($query) => $query->where('group', $request->string('group')->toString()))
            ->when($request->filled('is_public'), fn ($query) => $query->where('is_public', $request->boolean('is_public')))
            ->orderBy('group')
            ->orderBy('key')
            ->paginate($this->perPage($request));

        return SettingResource::collection($settings);
    }

    public function publicSettings(): AnonymousResourceCollection
    {
        return SettingResource::collection($this->settingsService->getPublicSettings());
    }

    public function show(Setting $setting): SettingResource
    {
        $this->authorize('view', $setting);

        return new SettingResource($setting);
    }

    public function update(UpdateSettingRequest $request, Setting $setting): SettingResource
    {
        $this->authorize('update', $setting);

        $data = $request->validated();
        $setting = $this->settingsService->updateSettingValue(
            $setting->key,
            array_key_exists('value', $data) ? $data['value'] : $setting->value,
            $data['type'] ?? $setting->type
        );

        $setting->update(array_intersect_key($data, array_flip([
            'group',
            'description',
            'is_public',
        ])));

        return new SettingResource($setting);
    }

    public function storeAdminPreference(StoreAdminPreferenceRequest $request): AdminPreferenceResource
    {
        $data = $request->validated();
        abort_unless((int) $data['user_id'] === auth()->user()->id, 403);

        $preference = $this->settingsService->updateAdminPreference(
            auth()->user()->id,
            $data['key'],
            $data['value'] ?? null,
            $data['type'] ?? 'string'
        );

        return new AdminPreferenceResource($preference);
    }

    public function updateAdminPreference(UpdateAdminPreferenceRequest $request, string $key): AdminPreferenceResource
    {
        $data = $request->validated();

        $preference = $this->settingsService->updateAdminPreference(
            auth()->user()->id,
            $key,
            $data['value'] ?? null,
            $data['type'] ?? 'string'
        );

        return new AdminPreferenceResource($preference);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
