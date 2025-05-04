<?php

namespace Coolsam\Transactify\Gateways;

use Coolsam\Transactify\Models\PaymentIntegration;
use Coolsam\Transactify\Models\PaymentTransaction;
use Coolsam\Transactify\PaymentGateway;
use Coolsam\Transactify\Support\Utils;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\Builders\AmountBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\LinkDescription;
use PaypalServerSdkLib\Models\Money;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

class Paypal extends PaymentGateway
{
    public function getName(): string
    {
        return 'paypal';
    }

    /**
     * @throws \Throwable
     */
    public function initiatePayment(array $data, int $integrationId): RedirectResponse|Redirector
    {
        $integration = config('transactify.models.payment-integration',
            PaymentIntegration::class)::find($integrationId);
        if (! $integration) {
            throw new \Exception('Integration not found');
        }
        $client = $this->makeClient($integration, $data);
        // Save the transaction
        $collect = collect($data);

        $transaction = app(Utils::class)->createTransaction(
            integration: $integration, amount: $collect->get('amount'),
            narration: $collect->get('narration'),
            currency: $collect->get('currency'),
            reference: $collect->get('reference'),
            invoiceId: $collect->get('invoice_id')
        );
        $payload = $this->makeOrderPayload($transaction, $data);
        try {
            $response = $client->getOrdersController()->createOrder($payload);
            if ($response->isSuccess()) {
                /**
                 * @var Order $res
                 */
                $res = $response->getResult();
                $transaction->update([
                    'request_payload' => $payload,
                    'request_code' => $res->getId(),
                ]);
                $transaction->transactionInitiated(collect($res)->toArray(), 'Paypal Order initiated successfully. Order ID: '.collect($res)->get('id'));
                $redirection = $this->getApprovalLink(collect($res));

                if ($redirection) {
                    return redirect($redirection);
                } else {
                    $transaction->paymentFailed(collect($res)->toArray(), 'Failed to get approval link');
                    Log::error('Failed to get approval link');
                    throw new \Exception('Failed to get approval link');
                }
            } else {
                $transaction->paymentFailed(collect($response->getResult())->toArray(), $response->getReasonPhrase() ?? 'Failed to initiate payment');
                Log::error(collect($response->getResult()));
                throw new \Exception($response->getReasonPhrase() ?? 'Failed to initiate payment');
            }
        } catch (\Throwable $exception) {
            $transaction->paymentFailed(['error' => $exception->getMessage()], 'Failed to initiate payment');
            throw $exception;
        }
    }

    public function getLogo(): string
    {
        return 'https://www.paypalobjects.com/marketing/web/logos/paypal-mark-color.svg';
    }

    public function getDarkLogo(): string
    {
        return $this->getLogo();
    }

    private function getApprovalLink(Collection $response): string
    {
        Log::alert('FINDING APPROVAL LINK');
        Log::info($response);
        /**
         * @var Collection<LinkDescription> $links
         */
        $links = collect($response->get('links'));
        /**
         * @var LinkDescription $actionLink
         */
        $actionLink = $links->firstWhere(fn (LinkDescription $link) => in_array($link->getRel(), ['approve', 'payer-action']));

        return $actionLink->getHref();
    }

    private function makeClient(PaymentIntegration $integration, array $data): PaypalServerSdkClient
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

        return $client->build();
    }

    private function makeOrderPayload(PaymentTransaction $transaction, array $data): array
    {
        $integration = $transaction->getAttribute('paymentIntegration');
        $config = collect($integration->getAttribute('config') ?? []);
        $dataCollect = collect($data);
        $currency = $config->get('currency', 'USD');
        $amount = $dataCollect->get('amount');
        $narration = $dataCollect->get('narration');
        $reference = $transaction->reference ?? str(Str::ulid())->upper()->toString();
        $intent = strtolower($dataCollect->get('intent',
            '')) === 'authorize' ? CheckoutPaymentIntent::AUTHORIZE : CheckoutPaymentIntent::CAPTURE;
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
                        ->customId($transaction->getAttribute('reference'))
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
