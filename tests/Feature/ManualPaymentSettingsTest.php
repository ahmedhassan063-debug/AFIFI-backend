<?php

namespace Tests\Feature;

use App\Models\Setting;
use Tests\TestCase;

class ManualPaymentSettingsTest extends TestCase
{
    public function test_public_settings_expose_manual_payment_methods_safely(): void
    {
        Setting::query()->create([
            'key' => 'payment.instapay.enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.instapay.account_name',
            'value' => 'AFIFI Store',
            'type' => 'string',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.instapay.account_identifier',
            'value' => 'afifi@instapay',
            'type' => 'string',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.instapay.instructions',
            'value' => 'Transfer the order total.',
            'type' => 'string',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.vodafone_cash.enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.vodafone_cash.phone',
            'value' => '01000000000',
            'type' => 'string',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.vodafone_cash.account_name',
            'value' => 'AFIFI Store',
            'type' => 'string',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'payment.vodafone_cash.instructions',
            'value' => 'Send the order total to this wallet.',
            'type' => 'string',
            'group' => 'payment',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'checkout.max_cart_quantity',
            'value' => '10',
            'type' => 'integer',
            'group' => 'checkout',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/settings/public');

        $response->assertOk();
        $response->assertJsonCount(8, 'data');
        $response->assertJsonPath('data.0.key', 'payment.instapay.account_identifier');
        $response->assertJsonPath('data.0.value', 'afifi@instapay');
        $response->assertJsonPath('data.1.key', 'payment.instapay.account_name');
        $response->assertJsonPath('data.2.key', 'payment.instapay.enabled');
        $response->assertJsonPath('data.2.value', true);
        $response->assertJsonPath('data.5.key', 'payment.vodafone_cash.enabled');
        $response->assertJsonPath('data.5.value', false);
        $response->assertJsonMissing(['key' => 'checkout.max_cart_quantity']);
    }
}
