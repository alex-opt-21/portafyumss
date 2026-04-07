<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nombre = $this->input('nombre', $this->input('name'));
        $tipo = $this->input('tipo', 'tecnica');

        $this->merge([
            'nombre' => $this->normalizeText($nombre),
            'nivel_cuantitativo' => $this->input('nivel_cuantitativo', $this->input('level')),
            'nivel_cualitativo' => $this->input('nivel_cualitativo', $this->mapLevelToPoints(
                $this->input('nivel_cualitativo', $this->input('level'))
            )),
            'tipo' => $tipo === 'Blandas' ? 'blanda' : $tipo,
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
                    ->where(fn ($query) => $query
                        ->where('usuario_id', $this->user()?->id)
                        ->where('tipo', $this->input('tipo')))
                    ->ignore($this->route('id')),
            ],
            'tipo' => ['required', 'in:tecnica,blanda'],
            'nivel_cuantitativo' => ['required', 'in:Junior,Mid,Senior'],
            'nivel_cualitativo' => ['nullable', 'integer', 'min:1', 'max:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya registraste esta habilidad en la misma categoria.',
            'nombre.min' => 'El nombre de la habilidad es demasiado corto.',
            'nivel_cuantitativo.in' => 'El nivel debe ser Junior, Mid o Senior.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la habilidad',
            'tipo' => 'categoria',
            'nivel_cuantitativo' => 'nivel cuantitativo',
        ];
    }

    private function mapLevelToPoints(?string $level): ?int
    {
        return match ($level) {
            'Junior' => 1,
            'Mid' => 2,
            'Senior' => 3,
            default => null,
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
