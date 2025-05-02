<?php

return [
    'tables' => [
        'payment-integrations' => 'payment_integrations',
        'payment-transactions' => 'payment_transactions',
        'transaction-histories' => 'transaction_histories',
    ],
    'models' => [
        'payment-integration' => \Coolsam\Transactify\Models\PaymentIntegration::class,
        'payment-transaction' => \Coolsam\Transactify\Models\PaymentTransaction::class,
        'transaction-history' => \Coolsam\Transactify\Models\TransactionHistory::class,
    ],
    'extend' => [
        /**
         * You can put all your gateway classes in various directories and specify the directories below. The gateways will be automatically discovered and registered.
         * Each gateway class must extend the base PaymentGateway class.
         */
        'discover-gateways' => [
            //            app_path('Payments/Gateways'),
        ],

        /**
         * You can also add your own gateways here in case they are not in the discover directory above.
         * Note: The order of override is default gateways -> discover gateways -> custom gateways.
         * This means that if a gateway is in both the discover directory and the custom gateways, the custom gateway will be used.
         * The unique identifier for each gateway is the gateway name which can be obtained via the getName() method of the gateway class.
         */
        'gateways' => [
            //            App\Payments\Gateways\PayStack::class,
            //            App\Payments\Gateways\Mollie::class,
            //            App\Payments\Gateways\Mpesa::class,
        ],
    ],
];
