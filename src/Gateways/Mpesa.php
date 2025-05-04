<?php

namespace Coolsam\Transactify\Gateways;

use Coolsam\Transactify\PaymentGateway;

class Mpesa extends PaymentGateway
{
    public function getName(): string
    {
        return 'mpesa';
    }

    public function initiatePayment(array $data, int $integrationId): array
    {
        return $data;
    }

    public function getLogo(): string
    {
        return 'https://static.cdnlogo.com/logos/m/95/m-pesa.svg';
    }
}
