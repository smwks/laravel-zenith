<?php

namespace SMWks\LaravelZenith\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SMWks\LaravelZenith\Zenith
 */
class Zenith extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SMWks\LaravelZenith\Zenith::class;
    }
}
