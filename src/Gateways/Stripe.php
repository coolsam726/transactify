<?php

namespace Coolsam\Transactify\Gateways;

use Coolsam\Transactify\PaymentGateway;

class Stripe extends PaymentGateway
{
    public function getName(): string
    {
        return 'stripe';
    }

    public function initiatePayment(array $data): array
    {
        return $data;
    }

    public function getLogo(): string
    {
        return 'https://images.stripeassets.com/fzn2n1nzq965/HTTOloNPhisV9P4hlMPNA/cacf1bb88b9fc492dfad34378d844280/Stripe_icon_-_square.svg?q=80&w=1082';
    }

    public function getDarkLogo(): string
    {
        return $this->getLogo();
    }
}
