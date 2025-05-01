<?php

namespace Coolsam\Transactify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Coolsam\Transactify\Transactify
 */
class Transactify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Coolsam\Transactify\Transactify::class;
    }
}
