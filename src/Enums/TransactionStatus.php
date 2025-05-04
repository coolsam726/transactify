<?php

namespace Coolsam\Transactify\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case INITIATED = 'initiated';
    case SUCCESS = 'success';

    case PARTIAL = 'partial';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
    case DISPUTED = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::INITIATED => 'Initiated',
            self::SUCCESS => 'Success',
            self::PARTIAL => 'Paid Partially',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::EXPIRED => 'Expired',
            self::DISPUTED => 'Disputed',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'The transaction is pending.',
            self::INITIATED => 'The transaction has been initiated.',
            self::SUCCESS => 'The transaction was successful.',
            self::PARTIAL => 'The transaction has been paid partially',
            self::FAILED => 'The transaction failed.',
            self::CANCELLED => 'The transaction was cancelled.',
            self::REFUNDED => 'The transaction was refunded.',
            self::EXPIRED => 'The transaction has expired.',
            self::DISPUTED => 'The transaction is disputed.',
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::SUCCESS, self::FAILED, self::CANCELLED, self::REFUNDED, self::EXPIRED, self::DISPUTED => true,
            default => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    public function appColor(): string
    {
        return match ($this) {
            self::PENDING, self::PARTIAL, self::DISPUTED => 'warning',
            self::INITIATED => 'info',
            self::SUCCESS => 'success',
            self::FAILED, self::EXPIRED, self::CANCELLED, self::REFUNDED => 'danger',
        };
    }
}
