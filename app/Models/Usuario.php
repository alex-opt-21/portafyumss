<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

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

    // 🔹 RELACIÓN NECESARIA PARA LA BÚSQUEDA
    public function habilidades()
    {
        return $this->hasMany(\App\Models\Habilidad::class, 'usuario_id');
    }

    /*public function profile()
    {
        return $this->hasOne(Profile::class, 'usuario_id');
    }*/
}