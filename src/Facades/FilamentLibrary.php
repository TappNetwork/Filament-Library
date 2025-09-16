<?php

namespace Tapp\FilamentLibrary\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tapp\FilamentLibrary\FilamentLibrary
 */
class FilamentLibrary extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Tapp\FilamentLibrary\FilamentLibrary::class;
    }
}
