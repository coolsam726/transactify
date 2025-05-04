<?php

namespace Coolsam\Transactify\Concerns;

use Coolsam\Transactify\Enums\TransactionStatus;
use Coolsam\Transactify\Models\PaymentTransaction;
use Coolsam\Transactify\Models\TransactionHistory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @mixin PaymentTransaction
 */
trait HasTransactionHistory
{
    public function transactionHistories(): HasMany
    {
        return $this->hasMany(config('transactify.models.transaction-history', TransactionHistory::class),'transaction_id');
    }
    public function recordHistory(TransactionStatus $status, ?array $response = [], ?string $message = null): TransactionHistory
    {
        /**
         * @var TransactionHistory $model
         */
        $model = config('transactify.models.transaction-history', TransactionHistory::class);
        $res = $model::create([
            'transaction_status' => $status->value,
            'transaction_id' => $this->getKey(),
            'description' => $message ?? $status->description(),
            'response_payload' => $response,
            'actor_id' => auth()->check() ? auth()->id() : null,
        ]);

        // Update the transaction status
        $this->update([
            'status' => $status->value,
            ...(filled($response) ? ['response_payload'=> $response] : []),
        ]);

        return $res;
    }

    public function updateTransactionFromHistory(): false|static
    {
        $latestHistory = $this->transactionHistories()->latest()->first();
        if (!$latestHistory) {
            return false;
        }

        $this->update([
            'status' => $latestHistory->getAttribute('transaction_status'),
        ]);

        return $this;
    }
    public function updateLatestHistory(?string $message = null, ?array $response = []): TransactionHistory|false
    {
        /**
         * @var TransactionHistory $latestHistory
         */
        $latestHistory = $this->transactionHistories()->latest()->first();
        if (!$latestHistory) {
            return false;
        }
        $status = TransactionStatus::from($latestHistory->transaction_status);
        if (!$status->isFinal()) {
            $latestHistory->update([
                'description' => $message ?? $latestHistory->description ?? $status->description(),
                'response_payload' => $response,
                'actor_id' => auth()->check() ? auth()->id() : $latestHistory->actor_id,
            ]);
        }
        return $latestHistory->refresh();
    }
    protected static function bootHasTransactionHistory(): void
    {
        static::creating(function (PaymentTransaction $transaction) {
            if (!$transaction->status) {
                $transaction->status = TransactionStatus::PENDING->value;
            }
        });
        static::created(function (PaymentTransaction $transaction) {
            $transaction->recordHistory(TransactionStatus::PENDING, message: "The transaction has been created and is pending payment");
        });
    }

    // Add events
    public function transactionInitiated(?array $response = [], ?string $message = null): void
    {
        $this->recordHistory(TransactionStatus::INITIATED,response: $response, message: $message);
    }

    public function transactionSuccessful(array $response, ?string $message = null): void
    {
        $this->recordHistory(TransactionStatus::SUCCESS,
            response: $response,
            message: $message ?? 'Payment completed successfully.');
    }

    public function paidPartially(array $response, ?string $message = null): void
    {
        $this->recordHistory(TransactionStatus::PARTIAL, response: $response, message: $message);
    }

    public function paymentFailed(array $response, ?string $message = null): void
    {
        $this->recordHistory(TransactionStatus::FAILED, response: $response,message: $message);
    }

    public function paymentCancelled(?array $response = [], ?string $message = null): void
    {
        $this->recordHistory(TransactionStatus::CANCELLED, response: $response, message: $message);
    }

    public function paymentExpired(?array $response = [], ?string $message = null): void
    {
        $this->recordHistory(TransactionStatus::EXPIRED, response: $response, message: $message);
    }

    public function getHistoryAttribute(): Collection
    {
        return $this->transactionHistories()->latest()->get();
    }
}