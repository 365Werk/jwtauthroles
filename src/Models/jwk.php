<?php

namespace werk365\jwtauthroles\Models;

use Illuminate\Database\Eloquent\Model;

class jwk extends Model
{
    //
    protected $fillable = ['kid', 'key'];
}
