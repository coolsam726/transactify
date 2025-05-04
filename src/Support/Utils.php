<?php

namespace Coolsam\Transactify\Support;

use Coolsam\Transactify\Enums\TransactionStatus;
use Coolsam\Transactify\Models\PaymentIntegration;
use Coolsam\Transactify\Models\PaymentTransaction;
use Coolsam\Transactify\PaymentGateway;
use Coolsam\Transactify\Transactify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use Str;
use Throwable;

class Utils
{
    public function getClassNamespaceFromFile(string $classPath): string
    {
        try {
            $className = $this->getClassNameFromFile($classPath);
            $reflection = new ReflectionClass($className);

            return $reflection->getNamespaceName();
        } catch (ReflectionException $e) {
            // Handle the exception if the class does not exist or is not valid
            return '';
        }
    }

    public function getClassNameFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if (preg_match('/namespace\s+(.+?);.*class\s+(\w+)/s', $contents, $matches)) {
            return $matches[1].'\\'.$matches[2];
        }

        return null;
    }

    public function getGateway(int|string $paymentIntegrationId): ?PaymentGateway
    {
        $integrationModel = \Config::get('transactify.models.payment-integration', PaymentIntegration::class);
        $integration = $integrationModel::find($paymentIntegrationId);
        if ($integration) {
            $class = $integration->getAttribute('gateway_class');

            return new $class;
        }

        return null;
    }

    public function renderLogo(string $gateway, bool $dark = false)
    {
        try {
            $gateway = app(Transactify::class)->gateway($gateway);
            if ($dark) {
                $logo = $gateway->getDarkLogo();
            } else {
                $logo = $gateway->getLogo();
            }
            // check if it is a url
            if (str($logo)->startsWith('http')) {
                return '<img src="'.$logo.'" alt="'.$gateway->getName().'" class="h-10 rounded" />';
            } elseif (str($logo)->startsWith('<')) {
                return "<div class='h-10'> $logo </div>";
            } else {
                // check if it is a file
                if (file_exists($logo)) {
                    return '<img src="'.asset($logo).'" alt="'.$gateway->getName().'" class="h-10 rounded" />';
                } else {
                    return '';
                }
            }
        } catch (Throwable $exception) {
            return '';
        }
    }

    public function getGatewayOptions(): Collection
    {
        $gateways = app(Transactify::class)->getGateways();

        return collect($gateways)->mapWithKeys(function (PaymentGateway $gateway) {
            return [$gateway->getName() => $gateway->getDisplayName()];
        });
    }

    /**
     * @throws Throwable
     */
    public function createTransaction(
        PaymentIntegration $integration,
        float $amount,
        string $narration = null,
        string $currency = null,
        string $reference = null,
        string $invoiceId = null,
        ?Model $payable = null
    ): PaymentTransaction {
        /**
         * @var PaymentTransaction $transactionModel
         */
        $transactionModel = \Config::get('transactify.models.payment-transaction', PaymentTransaction::class);
        $transaction = new $transactionModel();
        /**
         * @var PaymentGateway $gateway
         */
        $gateway = $integration->gateway;

        $currency = $currency ?? $integration->getConfig('default_currency', $gateway->getDefaultCurrency());
        $reference = $reference ?? str(Str::ulid())->upper()->toString();
        $narration = $narration ?? 'Payment for order '.$reference;
        $transaction->setAttribute('payment_integration_id', $integration->id);
        $transaction->setAttribute('reference', $reference);
        $transaction->setAttribute('narration', $narration);
        $transaction->setAttribute('request_currency', $currency);
        $transaction->setAttribute('request_amount', $amount);
        $transaction->setAttribute('payable_type', $payable?->getMorphClass());
        $transaction->setAttribute('payable_id', $payable?->getKey());
        $transaction->setAttribute('status', TransactionStatus::PENDING->value);
        $transaction->setAttribute('invoice_id', $invoiceId);
        $transaction->setAttribute('paid_amount', 0);
        $transaction->setAttribute('payment_currency', $currency);
        $transaction->saveOrFail();
        return $transaction;
    }
}
