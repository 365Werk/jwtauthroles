<?php

namespace werk365\jwtauthroles\Models;

use App\User;
use Spatie\Permission\Traits\HasRoles;

class JwtUser extends User
{
    use HasRoles;
    protected $guard_name = 'jwt';
    protected $fillable = ['uuid', 'roles', 'claims'];
}
