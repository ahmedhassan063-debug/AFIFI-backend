<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CoreSettingSeeder extends Seeder
{
    /**
     * Seed core settings.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'site.name', 'value' => 'AFIFI', 'type' => 'string', 'group' => 'site', 'description' => 'Public site name.', 'is_public' => true],
            ['key' => 'site.support_phone', 'value' => null, 'type' => 'string', 'group' => 'site', 'description' => 'Support phone number.', 'is_public' => true],
            ['key' => 'site.support_email', 'value' => null, 'type' => 'string', 'group' => 'site', 'description' => 'Support email address.', 'is_public' => true],
            ['key' => 'site.whatsapp_number', 'value' => null, 'type' => 'string', 'group' => 'site', 'description' => 'WhatsApp contact number.', 'is_public' => true],
            ['key' => 'store.default_currency', 'value' => 'EGP', 'type' => 'string', 'group' => 'store', 'description' => 'Default store currency code.', 'is_public' => true],
            ['key' => 'checkout.max_cart_quantity', 'value' => '10', 'type' => 'integer', 'group' => 'checkout', 'description' => 'Maximum quantity per cart item.', 'is_public' => false],
            ['key' => 'shipping.free_shipping_min_subtotal', 'value' => null, 'type' => 'decimal', 'group' => 'shipping', 'description' => 'Minimum subtotal for free shipping.', 'is_public' => true],
            ['key' => 'orders.auto_confirm', 'value' => 'false', 'type' => 'boolean', 'group' => 'orders', 'description' => 'Whether orders are auto-confirmed.', 'is_public' => false],
            ['key' => 'media.default_disk', 'value' => 'public', 'type' => 'string', 'group' => 'media', 'description' => 'Default media storage disk.', 'is_public' => false],
            ['key' => 'seo.default_title', 'value' => 'AFIFI', 'type' => 'string', 'group' => 'seo', 'description' => 'Default SEO title.', 'is_public' => true],
            ['key' => 'seo.default_description', 'value' => 'AFIFI online store.', 'type' => 'string', 'group' => 'seo', 'description' => 'Default SEO description.', 'is_public' => true],
            ['key' => 'payment.instapay.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Whether InstaPay manual payments are enabled.', 'is_public' => true],
            ['key' => 'payment.instapay.account_name', 'value' => null, 'type' => 'string', 'group' => 'payment', 'description' => 'InstaPay account holder name.', 'is_public' => true],
            ['key' => 'payment.instapay.account_identifier', 'value' => null, 'type' => 'string', 'group' => 'payment', 'description' => 'InstaPay account identifier (e.g. payment address).', 'is_public' => true],
            ['key' => 'payment.instapay.instructions', 'value' => null, 'type' => 'string', 'group' => 'payment', 'description' => 'Customer instructions for InstaPay transfers.', 'is_public' => true],
            ['key' => 'payment.vodafone_cash.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Whether Vodafone Cash manual payments are enabled.', 'is_public' => true],
            ['key' => 'payment.vodafone_cash.phone', 'value' => null, 'type' => 'string', 'group' => 'payment', 'description' => 'Vodafone Cash wallet phone number.', 'is_public' => true],
            ['key' => 'payment.vodafone_cash.account_name', 'value' => null, 'type' => 'string', 'group' => 'payment', 'description' => 'Vodafone Cash account holder name.', 'is_public' => true],
            ['key' => 'payment.vodafone_cash.instructions', 'value' => null, 'type' => 'string', 'group' => 'payment', 'description' => 'Customer instructions for Vodafone Cash transfers.', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                    'is_public' => $setting['is_public'],
                ],
            );
        }
    }
}
