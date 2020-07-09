<?php

namespace werk365\jwtauthroles\Facades;

use Illuminate\Support\Facades\Facade;

class jwtauthroles extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'jwtauthroles';
    }
}
