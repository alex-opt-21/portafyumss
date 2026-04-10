<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    private static array $schemaColumnCache = [];

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'proveedor',
        'proveedor_id',
        'biografia',
        'fecha_nacimiento',
        'ubicacion',
        'foto_perfil',
        'foto_portada',
        'url_cv',
        'perfil_completado',
        'estado',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'perfil_completado' => 'boolean',
    ];

    protected $appends = [
        'provider',
        'provider_id',
        'profesion',
    ];

    public $timestamps = true;

    public function habilidades()
    {
        return $this->hasMany(Habilidad::class, 'usuario_id');
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class, 'usuario_id');
    }

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class, 'usuario_id');
    }

    public function sociales()
    {
        return $this->hasMany(Social::class, 'usuario_id');
    }

    public function formacionAcademica()
    {
        return $this->hasMany(FormacionAcademica::class, 'usuario_id');
    }

    public function getProviderAttribute(): ?string
    {
        return $this->attributes['proveedor'] ?? ($this->attributes['provider'] ?? null);
    }

    public function getProviderIdAttribute(): ?string
    {
        return $this->attributes['proveedor_id'] ?? ($this->attributes['provider_id'] ?? null);
    }

    public function getProfesionAttribute(): string
    {
        return (string) ($this->attributes['profesion'] ?? '');
    }

    public static function persistenceData(array $attributes): array
    {
        return array_filter([
            'nombre' => $attributes['nombre'] ?? null,
            'apellido' => $attributes['apellido'] ?? null,
            'email' => $attributes['email'] ?? null,
            'password' => $attributes['password'] ?? null,
            'rol' => $attributes['rol'] ?? null,
            'proveedor' => self::hasColumnCached('usuarios', 'proveedor') ? ($attributes['proveedor'] ?? null) : null,
            'proveedor_id' => self::hasColumnCached('usuarios', 'proveedor_id') ? ($attributes['proveedor_id'] ?? null) : null,
            'provider' => self::hasColumnCached('usuarios', 'provider') ? ($attributes['proveedor'] ?? ($attributes['provider'] ?? null)) : null,
            'provider_id' => self::hasColumnCached('usuarios', 'provider_id') ? ($attributes['proveedor_id'] ?? ($attributes['provider_id'] ?? null)) : null,
            'biografia' => $attributes['biografia'] ?? null,
            'profesion' => self::hasColumnCached('usuarios', 'profesion') ? ($attributes['profesion'] ?? null) : null,
            'fecha_nacimiento' => $attributes['fecha_nacimiento'] ?? null,
            'ubicacion' => $attributes['ubicacion'] ?? null,
            'foto_perfil' => $attributes['foto_perfil'] ?? null,
            'foto_portada' => self::hasColumnCached('usuarios', 'foto_portada') ? ($attributes['foto_portada'] ?? null) : null,
            'url_cv' => self::hasColumnCached('usuarios', 'url_cv') ? ($attributes['url_cv'] ?? null) : null,
            'perfil_completado' => $attributes['perfil_completado'] ?? null,
            'estado' => $attributes['estado'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    private static function hasColumnCached(string $table, string $column): bool
    {
        $cacheKey = "{$table}.{$column}";

        if (! array_key_exists($cacheKey, self::$schemaColumnCache)) {
            self::$schemaColumnCache[$cacheKey] = Cache::rememberForever(
                "schema.column.{$cacheKey}",
                static fn () => Schema::hasColumn($table, $column)
            );
        }

        return self::$schemaColumnCache[$cacheKey];
    }
}
