<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilUsuario extends Model
{
    protected $table = 'profile';

    protected $fillable = [
        'usuario_id',
        'biografia',
        'universidad',
        'carrera',
        'ubicacion',
        'foto_perfil',
        'perfil_completado'
    ];
}
