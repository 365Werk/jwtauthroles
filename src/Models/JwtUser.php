<?php

namespace Werk365\JwtAuthRoles\Models;

use App\Models\User;

class JwtUser extends User
{
    protected $guard_name = 'jwt';
    protected $fillable = ['uuid', 'roles', 'claims'];
}
