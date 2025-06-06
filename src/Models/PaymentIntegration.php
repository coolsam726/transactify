<?php

namespace Coolsam\Transactify\Models;

use Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentIntegration extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return Config::get('transactify.tables.payment-integrations', 'payment_integrations');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo('payable');
    }

    public function transactions()
    {
        return $this->hasMany(Config::get('transactify.models.payment-transaction', 'Coolsam\Transactify\Models\PaymentTransaction'), 'payment_integration_id');
    }
}
