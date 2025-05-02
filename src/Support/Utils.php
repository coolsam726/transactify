<?php

namespace Coolsam\Transactify\Support;

use Coolsam\Transactify\Models\PaymentIntegration;
use Coolsam\Transactify\PaymentGateway;
use Coolsam\Transactify\Transactify;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
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
            return $matches[1] . '\\' . $matches[2];
        }

        return null;
    }

    public function getGateway(int|string $paymentIntegrationId): ?PaymentGateway
    {
        $integrationModel = \Config::get('transactify.models.payment-integration', PaymentIntegration::class);
        $integration = $integrationModel::find($paymentIntegrationId);
        if ($integration) {
            $class = $integration->getAttribute('gateway_class');
            return new $class();
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
                return '<img src="' . $logo . '" alt="' . $gateway->getName() . '" class="h-10 rounded" />';
            } elseif (str($logo)->startsWith('<')) {
                return "<div class='h-10'> $logo </div>";
            } else {
                // check if it is a file
                if (file_exists($logo)) {
                    return '<img src="' . asset($logo) . '" alt="' . $gateway->getName() . '" class="h-10 rounded" />';
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
}
