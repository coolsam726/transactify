<?php

namespace Coolsam\Transactify\Gateways;

use Coolsam\Transactify\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class Paypal extends PaymentGateway
{
    public function getName(): string
    {
        return 'paypal';
    }

    public function initiatePayment(array $data): RedirectResponse|Redirector
    {
        return redirect('https://paypal.com');
    }

    public function getLogo(): string
    {
        return 'https://www.paypalobjects.com/marketing/web/logos/paypal-mark-color.svg';
    }

    public function getDarkLogo(): string
    {
        return $this->getLogo();
    }
}
