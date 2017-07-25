<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SignUser extends Model
{
    //
    protected $table = 'zt_zqqd_bind';
    public $timestamps = false;
    protected $primaryKey = 'openid';
}
