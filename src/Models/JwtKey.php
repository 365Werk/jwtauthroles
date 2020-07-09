<?php

namespace werk365\jwtauthroles\Models;

use Illuminate\Database\Eloquent\Model;

class JwtKey extends Model
{
    //
    protected $fillable = ['kid', 'key'];
}
