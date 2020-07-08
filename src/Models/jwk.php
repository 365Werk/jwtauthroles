<?php

namespace werk365\jwtfusionauth\Models;

use Illuminate\Database\Eloquent\Model;

class jwk extends Model
{
    //
    protected $fillable = ['kid', 'key'];
}
