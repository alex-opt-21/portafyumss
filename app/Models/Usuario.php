<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'rol',
        'provider',
        'provider_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public $timestamps = true;
}