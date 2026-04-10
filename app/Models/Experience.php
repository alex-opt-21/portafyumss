<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Experience extends Model
{
    private static ?string $resolvedTable = null;

    protected $fillable = [
        'usuario_id',
        'tipo',
        'empresa',
        'cargo',
        'company',
        'title',
        'descripcion',
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
        'company',
        'title',
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
                'schema.table.experience',
                static fn () => Schema::hasTable('experiencias') ? 'experiencias' : 'experience'
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
        $table = $instance->getTable();

        if ($table === 'experiencias') {
            return array_filter([
                'empresa' => $attributes['empresa'] ?? null,
                'cargo' => $attributes['cargo'] ?? null,
                'descripcion' => $attributes['descripcion'] ?? null,
                'fecha_inicio' => $attributes['fecha_inicio'] ?? null,
                'fecha_fin' => $attributes['fecha_fin'] ?? null,
                'actualmente' => $attributes['actualmente'] ?? false,
            ], static fn ($value) => $value !== null);
        }

        return array_filter([
            'tipo' => 'laboral',
            'company' => $attributes['empresa'] ?? null,
            'title' => $attributes['cargo'] ?? null,
            'descripcion' => $attributes['descripcion'] ?? null,
            'fecha_inicio' => $attributes['fecha_inicio'] ?? null,
            'fecha_fin' => $attributes['fecha_fin'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    public function getCompanyAttribute(): ?string
    {
        return $this->attributes['empresa'] ?? ($this->attributes['company'] ?? null);
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['cargo'] ?? ($this->attributes['title'] ?? null);
    }

    public function getTypeAttribute(): string
    {
        return $this->attributes['tipo'] ?? 'laboral';
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
