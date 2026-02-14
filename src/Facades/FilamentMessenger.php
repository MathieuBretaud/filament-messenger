<?php

namespace MathieuBretaud\FilamentMessenger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MathieuBretaud\FilamentMessenger\FilamentMessenger
 */
class FilamentMessenger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MathieuBretaud\FilamentMessenger\FilamentMessenger::class;
    }
}
