<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Habilidad extends Model
{
    protected $table = 'habilidades';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'tipo',
        'nivel_texto',
        'nivel_numero',
        'nivel',
        'nivel_cuantitativo',
        'nivel_cualitativo',
    ];

    protected $casts = [
        'nivel_numero' => 'integer',
        'nivel' => 'integer',
    ];

    protected $appends = [
        'nivel_cuantitativo',
        'nivel_cualitativo',
        'level',
    ];

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('usuario_id', $userId);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public static function persistenceData(array $attributes): array
    {
        return array_filter([
            'nombre' => $attributes['nombre'] ?? null,
            'tipo' => $attributes['tipo'] ?? null,
            'nivel_texto' => Schema::hasColumn('habilidades', 'nivel_texto') ? ($attributes['nivel_texto'] ?? null) : null,
            'nivel_numero' => Schema::hasColumn('habilidades', 'nivel_numero') ? ($attributes['nivel_numero'] ?? null) : null,
            'nivel' => Schema::hasColumn('habilidades', 'nivel') ? ($attributes['nivel'] ?? null) : null,
            'nivel_cuantitativo' => Schema::hasColumn('habilidades', 'nivel_cuantitativo') ? ($attributes['nivel_texto'] ?? null) : null,
            'nivel_cualitativo' => Schema::hasColumn('habilidades', 'nivel_cualitativo') ? ($attributes['nivel_numero'] ?? null) : null,
        ], static fn ($value) => $value !== null);
    }

    public function getNivelCuantitativoAttribute(): ?string
    {
        if (isset($this->attributes['nivel_texto'])) {
            return $this->attributes['nivel_texto'];
        }

        if (isset($this->attributes['nivel_cuantitativo'])) {
            return $this->attributes['nivel_cuantitativo'];
        }

        return match ((int) ($this->attributes['nivel'] ?? 0)) {
            5, 4 => 'avanzado',
            3 => 'intermedio',
            2, 1 => 'basico',
            default => null,
        };
    }

    public function getNivelCualitativoAttribute(): ?int
    {
        if (isset($this->attributes['nivel_numero'])) {
            return (int) $this->attributes['nivel_numero'];
        }

        if (isset($this->attributes['nivel_cualitativo'])) {
            return (int) $this->attributes['nivel_cualitativo'];
        }

        return match ((int) ($this->attributes['nivel'] ?? 0)) {
            5 => 100,
            4 => 85,
            3 => 60,
            2 => 40,
            1 => 20,
            default => null,
        };
    }

    public function getLevelAttribute(): ?string
    {
        return $this->getNivelCuantitativoAttribute();
    }
}
