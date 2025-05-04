<?php

namespace Coolsam\Transactify\Models;

use Coolsam\Transactify\Concerns\HasTransactionHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;

class PaymentTransaction extends Model
{
    use HasTransactionHistory;
    protected $guarded = ['id'];

    public function getTable()
    {
        return Config::get('transactify.tables.payment-transactions', 'payment_transactions');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo('payable');
    }

    public function paymentIntegration(): BelongsTo
    {
        return $this->belongsTo(Config::get('transactify.models.payment-integration', PaymentIntegration::class), 'payment_integration_id');
    }

    public function transactionHistories(): HasMany
    {
        return $this->hasMany(Config::get('transactify.models.transaction-history', TransactionHistory::class), 'transaction_id', 'id');
    }
}
