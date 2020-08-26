<?php

namespace mttzzz\AmoClient\Facades;

use Illuminate\Support\Facades\Facade;

class AmoClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'amoclient';
    }
}
