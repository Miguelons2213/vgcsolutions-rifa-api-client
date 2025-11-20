<?php

namespace Rifa\ApiClient\Facades;

use Illuminate\Support\Facades\Facade;

class RifaApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'rifa-api';
    }
}
