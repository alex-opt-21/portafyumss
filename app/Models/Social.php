<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Social extends Model
{
    private static ?string $resolvedTable = null;

    protected $fillable = [
        'usuario_id',
        'plataforma',
        'url',
        'nombre_plataforma',
        'url_plataforma',
        'url_cv',
    ];

    protected $appends = [
        'nombre_plataforma',
        'url_plataforma',
    ];

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('usuario_id', $userId);
    }

    public function getTable()
    {
        if (self::$resolvedTable === null) {
            self::$resolvedTable = Cache::rememberForever(
                'schema.table.social',
                static fn () => Schema::hasTable('redes_sociales') ? 'redes_sociales' : 'social'
            );
        }

        return self::$resolvedTable;
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public static function platformIdentity(int $userId, string $platform): array
    {
        $instance = new static();

        if ($instance->getTable() === 'redes_sociales') {
            return [
                'usuario_id' => $userId,
                'plataforma' => $platform,
            ];
        }

        return [
            'usuario_id' => $userId,
            'nombre_plataforma' => $platform,
        ];
    }

    public static function persistenceData(array $attributes): array
    {
        $instance = new static();

        if ($instance->getTable() === 'redes_sociales') {
            return array_filter([
                'plataforma' => $attributes['plataforma'] ?? null,
                'url' => $attributes['url'] ?? null,
            ], static fn ($value) => $value !== null);
        }

        return array_filter([
            'nombre_plataforma' => $attributes['plataforma'] ?? null,
            'url_plataforma' => $attributes['url'] ?? null,
            'url_cv' => $attributes['cv_url'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    public function getNombrePlataformaAttribute(): ?string
    {
        return $this->attributes['plataforma'] ?? ($this->attributes['nombre_plataforma'] ?? null);
    }

    public function getPlataformaAttribute(): ?string
    {
        return $this->getNombrePlataformaAttribute();
    }

    public function getUrlPlataformaAttribute(): ?string
    {
        return $this->attributes['url'] ?? ($this->attributes['url_plataforma'] ?? null);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->getUrlPlataformaAttribute();
    }

    public function getUrlCvAttribute(): ?string
    {
        return $this->attributes['url_cv'] ?? null;
    }
}
