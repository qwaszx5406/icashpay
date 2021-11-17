<?php

namespace Icashpay\Api\Facades;

use Illuminate\Support\Facades\Facade;

class IcashpayApiFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'IcashpayApi';
    }
}