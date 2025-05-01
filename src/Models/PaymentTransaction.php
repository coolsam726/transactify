<?php

namespace Coolsam\Transactify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;

class PaymentTransaction extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return Config::get('transactify.tables.payment-transactions');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo('payable');
    }

    public function paymentIntegration(): BelongsTo
    {
        return $this->belongsTo(Config::get('transactify.models.payment-integration'), 'payment_integration_id');
    }
}
