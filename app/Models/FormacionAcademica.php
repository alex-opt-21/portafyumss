<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class FormacionAcademica extends Model
{
    private static ?string $resolvedTable = null;

    protected $fillable = [
        'usuario_id',
        'nivel_formacion',
        'tipo_formacion',
        'institucion',
        'nombre_programa',
        'nombre_carrera',
        'fecha_inicio',
        'fecha_fin',
        'actualmente',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'actualmente' => 'boolean',
    ];

    protected $appends = [
        'tipo_formacion',
        'nombre_carrera',
        'careerName',
        'type',
        'is_current',
        'isCurrent',
    ];

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('usuario_id', $userId);
    }

    public function getTable()
    {
        if (self::$resolvedTable === null) {
            self::$resolvedTable = Cache::rememberForever(
                'schema.table.formacion_academica',
                static fn () => Schema::hasTable('formaciones_academicas') ? 'formaciones_academicas' : 'formacion_academica'
            );
        }

        return self::$resolvedTable;
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public static function persistenceData(array $attributes): array
    {
        $instance = new static();

        if ($instance->getTable() === 'formaciones_academicas') {
            return array_filter([
                'nivel_formacion' => $attributes['nivel_formacion'] ?? null,
                'institucion' => $attributes['institucion'] ?? null,
                'nombre_programa' => $attributes['nombre_programa'] ?? null,
                'fecha_inicio' => $attributes['fecha_inicio'] ?? null,
                'fecha_fin' => $attributes['fecha_fin'] ?? null,
                'actualmente' => $attributes['actualmente'] ?? false,
            ], static fn ($value) => $value !== null);
        }

        return array_filter([
            'tipo_formacion' => $attributes['nivel_formacion'] ?? null,
            'institucion' => $attributes['institucion'] ?? null,
            'nombre_carrera' => $attributes['nombre_programa'] ?? null,
            'fecha_inicio' => $attributes['fecha_inicio'] ?? null,
            'fecha_fin' => $attributes['fecha_fin'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    public function getTipoFormacionAttribute(): ?string
    {
        return $this->attributes['nivel_formacion'] ?? ($this->attributes['tipo_formacion'] ?? null);
    }

    public function getNivelFormacionAttribute(): ?string
    {
        return $this->getTipoFormacionAttribute();
    }

    public function getNombreCarreraAttribute(): ?string
    {
        return $this->attributes['nombre_programa'] ?? ($this->attributes['nombre_carrera'] ?? null);
    }

    public function getNombreProgramaAttribute(): ?string
    {
        return $this->getNombreCarreraAttribute();
    }

    public function getCareerNameAttribute(): ?string
    {
        return $this->getNombreCarreraAttribute();
    }

    public function getTypeAttribute(): ?string
    {
        return $this->getTipoFormacionAttribute();
    }

    public function getIsCurrentAttribute(): bool
    {
        if (array_key_exists('actualmente', $this->attributes)) {
            return (bool) $this->attributes['actualmente'];
        }

        return empty($this->attributes['fecha_fin']);
    }

    public function getIsCurrentCamelAttribute(): bool
    {
        return $this->getIsCurrentAttribute();
    }
}
