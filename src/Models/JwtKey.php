<?php

namespace Werk365\JwtAuthRoles\Models;

use Illuminate\Database\Eloquent\Model;

class JwtKey extends Model
{
    //
    protected $fillable = ['kid', 'key'];
}
