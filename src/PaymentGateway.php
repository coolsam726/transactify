<?php

namespace Coolsam\Transactify;

use Coolsam\Transactify\Contracts\PaymentGatewayContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

abstract class PaymentGateway implements PaymentGatewayContract
{
    abstract public function getName(): string;

    public function getDisplayName(): string
    {
        return str($this->getName())->pascal()->snake()->title()->replace('_', ' ')->toString();
    }

    public function getLogo(): string
    {
        return 'https://ui-avatars.com/api/?name='.$this->getDisplayName().'&size=512&bold=true&format=svg';
    }

    public function getDarkLogo(): string
    {
        return 'https://ui-avatars.com/api/?name='.$this->getName().'&size=512&bold=true&format=svg&background=0a0a0a&&color=CCCCCC';
    }

    abstract public function initiatePayment(array $data, int $integrationId): RedirectResponse|Redirector|array;

    public function handleCallback(array $data): array
    {
        return $data;
    }

    public function handleWebhook(array $data): array
    {
        return $data;
    }

    public function getSupportedCurrencies(): array
    {
        return [];
    }

    public function getSupportedCountries(): array
    {
        return [];
    }

    public function getDefaultCurrency(): string
    {
        return 'USD';
    }
}
