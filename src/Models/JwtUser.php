<?php

namespace Werk365\JwtAuthRoles\Models;

class_alias(config('jwtauthroles.userModel'), 'Werk365\\JwtAuthRoles\\Models\\AppUser');

class JwtUser extends AppUser
{
    protected $guard_name = 'jwt';
    protected $fillable = ['uuid', 'roles', 'claims'];
}
