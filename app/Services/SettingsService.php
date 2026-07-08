<?php

namespace App\Services;

use App\Models\AdminPreference;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        return $this->castValue($setting->value, $setting->type);
    }

    public function getPublicSettings(): Collection
    {
        $settings = Setting::query()
            ->where('is_public', true)
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        $settings->each(function (Setting $setting) {
            $setting->value = $this->castValue($setting->value, $setting->type);
        });

        return $settings;
    }

    public function updateSettingValue(string $key, mixed $value, ?string $type = null): Setting
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);

        $setting->value = $this->prepareValue($value, $type ?? $setting->type ?? 'string');

        if ($type !== null) {
            $setting->type = $type;
        }

        $setting->save();

        return $setting;
    }

    public function getAdminPreference(int $userId, string $key, mixed $default = null): mixed
    {
        $preference = AdminPreference::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->first();

        if (! $preference) {
            return $default;
        }

        return $this->castValue($preference->value, $preference->type);
    }

    public function updateAdminPreference(int $userId, string $key, mixed $value, string $type = 'string'): AdminPreference
    {
        return AdminPreference::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'key' => $key,
            ],
            [
                'value' => $this->prepareValue($value, $type),
                'type' => $type,
            ]
        );
    }

    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    private function prepareValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'boolean') {
            return $value ? '1' : '0';
        }

        if ($type === 'json') {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
