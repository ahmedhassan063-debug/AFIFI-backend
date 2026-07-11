<?php

namespace App\Services;

use RuntimeException;

class ManualPaymentService
{
    public const METHOD_INSTAPAY = 'instapay';

    public const METHOD_VODAFONE_CASH = 'vodafone_cash';

    public const SUPPORTED_METHODS = [
        self::METHOD_INSTAPAY,
        self::METHOD_VODAFONE_CASH,
    ];

    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function normalizePaymentMethod(string $paymentMethod): string
    {
        return strtolower(trim($paymentMethod));
    }

    public function isSupportedMethod(string $paymentMethod): bool
    {
        return in_array($this->normalizePaymentMethod($paymentMethod), self::SUPPORTED_METHODS, true);
    }

    public function isMethodEnabled(string $paymentMethod): bool
    {
        $method = $this->normalizePaymentMethod($paymentMethod);

        return (bool) $this->settingsService->get($this->enabledSettingKey($method), false);
    }

    public function assertPaymentMethodEnabled(string $paymentMethod): void
    {
        $method = $this->normalizePaymentMethod($paymentMethod);

        if (! $this->isSupportedMethod($method)) {
            throw new RuntimeException('Unsupported payment method.');
        }

        if (! $this->isMethodEnabled($method)) {
            throw new RuntimeException('Selected payment method is not available.');
        }
    }

    public function normalizeProviderReference(string $reference): string
    {
        return trim($reference);
    }

    private function enabledSettingKey(string $method): string
    {
        return match ($method) {
            self::METHOD_INSTAPAY => 'payment.instapay.enabled',
            self::METHOD_VODAFONE_CASH => 'payment.vodafone_cash.enabled',
            default => throw new RuntimeException('Unsupported payment method.'),
        };
    }
}
