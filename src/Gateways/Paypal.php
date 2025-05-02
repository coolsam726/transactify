<?php

namespace Coolsam\Transactify\Gateways;

use Coolsam\Transactify\Models\PaymentIntegration;
use Coolsam\Transactify\Models\PaymentTransaction;
use Coolsam\Transactify\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\Builders\AmountBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\Money;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

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

    private function makeClient(PaymentIntegration $integration, array $data): PaypalServerSdkClientBuilder
    {
        $config = collect($integration->getAttribute(
            'config') ?? []);
        $clientId = $config->get('client_id');
        $clientSecret = $config->get('client_secret');
        $mode = $config->get('environment', 'sandbox');
        $client = PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init(
                    $clientId,
                    $clientSecret
                )
            );

        $client->environment(strtolower($mode) === 'live' ? Environment::PRODUCTION : Environment::SANDBOX);

        return $client;
    }

    private function makeOrderPayload(PaymentTransaction $transaction, array $data): array
    {
        $integration = $transaction->paymentIntegration;
        $config = collect($integration->getAttribute('config') ?? []);
        $dataCollect = collect($data);
        $currency = $config->get('currency', 'USD');
        $amount = $dataCollect->get('amount');
        $narration = $dataCollect->get('narration');
        $reference = $transaction->reference ?? str(Str::ulid())->upper()->toString();
        $intent = strtolower($dataCollect->get('intent', '')) === 'authorize' ? CheckoutPaymentIntent::AUTHORIZE : CheckoutPaymentIntent::CAPTURE;
        $breakdown = AmountBreakdownBuilder::init();
        if ($dataCollect
            ->get('discount')) {
            $breakdown->discount(new Money($currency, str($dataCollect->get('discount', 0))));
        }

        if ($dataCollect->get('shipping')) {
            $breakdown->shipping(new Money($currency, str($dataCollect->get('shipping', 0))));
        }

        if ($dataCollect->get('shipping_discount')) {
            $breakdown->shippingDiscount(new Money($currency, str($dataCollect->get('shipping_discount', 0))));
        }

        if ($dataCollect->get('handling')) {
            $breakdown->handling(new Money($currency, str($dataCollect->get('handling', 0))));
        }

        if ($dataCollect->get('insurance')) {
            $breakdown->insurance(new Money($currency, str($dataCollect->get('insurance', 0))));
        }

        if ($dataCollect
            ->get('tax_total')) {
            $breakdown->taxTotal(new Money($currency, str($dataCollect->get('tax', 0))));
        }

        if ($dataCollect->get('item_total')) {
            $breakdown->itemTotal(new Money($currency, str($dataCollect->get('item_total', 0))));
        }

        return [
            'body' => OrderRequestBuilder::init(
                $intent,
                [
                    PurchaseUnitRequestBuilder::init(
                        AmountWithBreakdownBuilder::init(
                            $currency,
                            $amount,
                        )
                            ->breakdown(
                                $breakdown->build()
                            )
                            ->build()
                    )
                        ->description(str($narration)->limit(127))
                        ->customId($transaction->reference)
                        ->invoiceId($dataCollect->get('invoice_id'))
                        ->build(),
                ]
            )
                ->build(),
            'prefer' => 'return=minimal',
        ];
    }

    private function makePaymentPayload(PaymentTransaction $transaction, array $data): array {}
}
