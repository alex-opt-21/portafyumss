<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $texto = $this->resolveNivelTexto();
        $numero = $this->resolveNivelNumero();

        $this->merge([
            'nombre' => $this->normalizeText($this->input('nombre', $this->input('name'))),
            'tipo' => $this->normalizeTipo($this->input('tipo', $this->input('category', 'tecnica'))),
            'nivel_texto' => $texto,
            'nivel_numero' => $numero,
            'nivel' => $this->resolveNivelLegacy($numero),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('habilidades', 'nombre')
                    ->where(fn ($query) => $query->where('usuario_id', $this->user()?->id))
                    ->ignore($this->route('id')),
            ],
            'tipo' => ['required', 'in:tecnica,blanda'],
            'nivel_texto' => ['nullable', 'in:basico,intermedio,avanzado,experto'],
            'nivel_numero' => ['nullable', 'integer', 'min:1', 'max:100'],
            'nivel' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya registraste esta habilidad.',
        ];
    }

    private function resolveNivelTexto(): ?string
    {
        $value = $this->input('nivel_texto', $this->input('nivel_cuantitativo', $this->input('level')));

        return match ($value) {
            'Junior' => 'basico',
            'Mid' => 'intermedio',
            'Senior' => 'avanzado',
            default => is_string($value) ? mb_strtolower(trim($value)) : null,
        };
    }

    private function resolveNivelNumero(): ?int
    {
        $value = $this->input('nivel_numero', $this->input('nivel_cualitativo'));

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        return match ($this->input('level')) {
            'Junior' => 30,
            'Mid' => 60,
            'Senior' => 85,
            default => null,
        };
    }

    private function resolveNivelLegacy(?int $numero): ?int
    {
        $legacy = $this->input('nivel');

        if ($legacy !== null && $legacy !== '') {
            return max(1, min(5, (int) $legacy));
        }

        if ($numero === null) {
            return match ($this->input('level')) {
                'Senior' => 4,
                'Mid' => 3,
                'Junior' => 2,
                default => null,
            };
        }

        if ($numero >= 80) return 4;
        if ($numero >= 50) return 3;
        if ($numero > 0) return 2;

        return null;
    }

    public function persistenceData(): array
    {
        $validated = $this->validated();

        $columns = [
            'nombre' => true,
            'tipo' => true,
            'nivel_texto' => Schema::hasColumn('habilidades', 'nivel_texto'),
            'nivel_numero' => Schema::hasColumn('habilidades', 'nivel_numero'),
            'nivel' => Schema::hasColumn('habilidades', 'nivel'),
            'nivel_cuantitativo' => Schema::hasColumn('habilidades', 'nivel_cuantitativo'),
            'nivel_cualitativo' => Schema::hasColumn('habilidades', 'nivel_cualitativo'),
        ];

        return array_intersect_key(
            $validated,
            array_filter($columns)
        );
    }

    private function normalizeTipo(mixed $value): string
    {
        $value = is_string($value) ? mb_strtolower(trim($value)) : 'tecnica';

        return match ($value) {
            'blandas', 'blanda' => 'blanda',
            default => 'tecnica',
        };
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($value));
    }
}
