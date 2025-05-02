<?php

namespace Coolsam\Transactify\Models;

use Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionHistory extends Model
{
    protected $guarded = [
        'id',
    ];
    protected $casts = [
        'response' => 'array',
    ];
    public function getTable()
    {
        return Config::get('transactify.tables.transaction-histories','transaction_histories');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Config::get('auth.providers.users.model', 'App\\Models\\User'), 'actor_id', 'id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Config::get('transactify.models.payment-transaction', PaymentTransaction::class), 'transaction_id', 'id');
    }
}
