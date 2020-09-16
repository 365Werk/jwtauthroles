<?php

namespace Werk365\JwtAuthRoles\Models;

use App\User;

class JwtUser extends User
{
    protected $guard_name = 'jwt';
    protected $fillable = ['uuid', 'roles', 'claims'];
}
