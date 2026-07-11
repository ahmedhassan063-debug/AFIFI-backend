<?php

namespace Tests\Support;

use App\Models\Setting;

trait EnablesManualPayments
{
    protected function seedEnabledManualPayments(array $overrides = []): void
    {
        $defaults = [
            'payment.instapay.enabled' => true,
            'payment.instapay.account_name' => 'AFIFI Store',
            'payment.instapay.account_identifier' => 'afifi@instapay',
            'payment.instapay.instructions' => 'Transfer the order total and submit your reference.',
            'payment.vodafone_cash.enabled' => true,
            'payment.vodafone_cash.phone' => '01000000000',
            'payment.vodafone_cash.account_name' => 'AFIFI Store',
            'payment.vodafone_cash.instructions' => 'Send the order total to this wallet and submit your reference.',
        ];

        $settings = array_merge($defaults, $overrides);

        foreach ($settings as $key => $value) {
            $type = is_bool($value) ? 'boolean' : 'string';

            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => $type,
                    'group' => 'payment',
                    'description' => 'Test payment setting.',
                    'is_public' => true,
                ],
            );
        }
    }
}
