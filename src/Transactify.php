<?php

namespace Coolsam\Transactify;

use Coolsam\Transactify\Support\Utils;

class Transactify
{
    public array $gateways = [];

    public function init(): Transactify|static
    {
        return $this->loadGateways();
    }

    protected function setGateways(array $gateways): static
    {
        $this->gateways = $gateways;

        return $this;
    }

    public function appendGateways(array $gateways): static
    {
        $this->gateways = array_merge($this->gateways, $gateways);

        return $this;
    }

    public function loadGateways(): static
    {
        try {
            $default = $this->discoverDefaultGateways();
            $custom1 = $this->discoverCustomGateways();
            $custom2 = $this->getCustomGateways();

            $gateways = array_merge($default, $custom1, $custom2);

            return $this->setGateways($gateways);
        } catch (\Throwable $exception) {
            return $this->setGateways([]);
        }
    }

    public function getGatewaysFromPath(string $absolutePath): array
    {
        $defaultGateways = [];
        $gatewayFiles = glob($absolutePath.'/*.php');
        if (! $gatewayFiles) {
            return [];
        }
        foreach ($gatewayFiles as $file) {
            $className = app(Utils::class)->getClassNameFromFile($file);
            if (class_exists($className)) {
                $gateway = new $className;
                if ($gateway instanceof PaymentGateway) {
                    $defaultGateways[$gateway->getName()] = $gateway;
                }
            }
        }

        return $defaultGateways;
    }

    public function discoverDefaultGateways(): array
    {
        $path = __DIR__.'/Gateways';

        return $this->getGatewaysFromPath($path);
    }

    public function discoverCustomGateways(): array
    {
        $paths = config('transactify.extend.discover-gateways', []);
        if (empty($paths)) {
            return [];
        }
        $customGateways = [];
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }
            $customGateways = array_merge($customGateways, $this->getGatewaysFromPath($path));
        }

        return $customGateways;
    }

    public function getCustomGateways(): array
    {
        $classes = config('transactify.extend.gateways', []);
        if (empty($classes)) {
            return [];
        }

        $customGateways = [];
        foreach ($classes as $class) {
            if (class_exists($class)) {
                $gateway = new $class;
                if ($gateway instanceof PaymentGateway) {
                    $customGateways[$gateway->getName()] = $gateway;
                }
            }
        }

        return $customGateways;
    }

    public function getGateways(array $only = []): array
    {
        $gateways = collect($this->gateways);
        if (! empty($only)) {
            $gateways = $gateways->filter(function ($gateway) use ($only) {
                return in_array($gateway->getName(), $only);
            });
        }

        return $gateways->toArray();
    }

    /**
     * @throws \Exception
     */
    public function gateway(string $name): PaymentGateway
    {
        if (array_key_exists($name, $this->gateways)) {
            return $this->gateways[$name];
        }

        throw new \Exception("Gateway {$name} not implemented.");
    }
}
