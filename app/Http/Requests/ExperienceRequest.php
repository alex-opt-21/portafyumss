<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $isCurrent = filter_var($this->input('is_current', $this->input('isCurrent')), FILTER_VALIDATE_BOOLEAN);
        $tipo = $this->input('tipo', $this->input('type'));

        $this->merge([
            'tipo' => $tipo === 'Trabajo' ? 'trabajo' : ($tipo === 'Academica' ? 'academica' : $tipo),
            'title' => $this->normalizeText($this->input('title', $this->input('cargo'))),
            'company' => $this->normalizeText($this->input('company', $this->input('empresa'))),
            'descripcion' => $this->normalizeText($this->input('descripcion', $this->input('description'))),
            'fecha_inicio' => $this->input('fecha_inicio', $this->input('startDate')),
            'fecha_fin' => $isCurrent ? null : $this->input('fecha_fin', $this->input('endDate')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:laboral,academica,trabajo'],
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'company' => ['required', 'string', 'min:2', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'tipo.in' => 'El tipo debe ser laboral o academica.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'cargo',
            'company' => 'empresa o institucion',
            'descripcion' => 'descripcion',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
        ];
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($value));
    }
}
