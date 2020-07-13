<?php

namespace werk365\jwtauthroles\Facades;

use Illuminate\Support\Facades\Facade;

class jwtAuthRoles extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'jwtAuthRoles';
    }
}
