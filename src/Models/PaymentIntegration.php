<?php

namespace Coolsam\Transactify\Models;

use Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class PaymentIntegration extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'config' => 'array',
    ];

    public function getTable()
    {
        return Config::get('transactify.tables.payment-integrations', 'payment_integrations');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo('payable');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Config::get('transactify.models.payment-transaction', 'Coolsam\Transactify\Models\PaymentTransaction'), 'payment_integration_id');
    }

    public function getGatewayAttribute()
    {
        return app($this->getAttribute('gateway_class'));
    }

    public function getConfigsAttribute(): Collection
    {
        return collect($this->getAttribute('config') ?? []);
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return collect($this->getAttribute('config') ?? [])->get($key, $default);
    }
}
