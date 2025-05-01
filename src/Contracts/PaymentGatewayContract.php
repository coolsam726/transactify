<?php

namespace Coolsam\Transactify\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

interface PaymentGatewayContract
{
    public function getName(): string;

    public function getDisplayName(): string;

    public function getLogo(): string;

    public function getDarkLogo(): string;

    public function initiatePayment(array $data): RedirectResponse|Redirector|array;

    public function handleCallback(array $data): array;

    public function handleWebhook(array $data): array;

    public function getSupportedCurrencies(): array;

    public function getSupportedCountries(): array;
}
