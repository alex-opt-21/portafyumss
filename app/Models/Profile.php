<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profile';

    protected $fillable = [
        'usuario_id',
        'biografia',
        'universidad',
        'carrera',
        'ubicacion',
        'foto_perfil',
        'perfil_completado',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
