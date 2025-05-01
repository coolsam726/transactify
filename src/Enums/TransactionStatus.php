<?php

namespace Coolsam\Transactify\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case INITIATED = 'initiated';
    case SUCCESS = 'success';
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
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::EXPIRED => 'Expired',
            self::DISPUTED => 'Disputed',
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
            self::PENDING, self::DISPUTED => 'warning',
            self::INITIATED => 'info',
            self::SUCCESS => 'success',
            self::FAILED, self::EXPIRED, self::CANCELLED, self::REFUNDED => 'danger',
        };
    }
}
