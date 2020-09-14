<?php

namespace werk365\jwtauthroles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class JwtUser extends Authenticatable
{
    //
    use HasRoles;
    protected $guard_name = 'jwt';
    protected $fillable = ['uuid', 'jwt'];
}
