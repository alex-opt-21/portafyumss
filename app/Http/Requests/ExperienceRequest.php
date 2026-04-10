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
        $current = filter_var(
            $this->input('actualmente', $this->input('is_current', $this->input('isCurrent'))),
            FILTER_VALIDATE_BOOLEAN
        );

        $this->merge([
            'empresa' => $this->normalizeText($this->input('empresa', $this->input('company'))),
            'cargo' => $this->normalizeText($this->input('cargo', $this->input('title'))),
            'descripcion' => $this->normalizeText($this->input('descripcion', $this->input('description'))),
            'fecha_inicio' => $this->input('fecha_inicio', $this->input('startDate')),
            'fecha_fin' => $current ? null : $this->input('fecha_fin', $this->input('endDate')),
            'actualmente' => $current,
        ]);
    }

    public function rules(): array
    {
        return [
            'empresa' => ['required', 'string', 'min:2', 'max:200'],
            'cargo' => ['required', 'string', 'min:2', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'actualmente' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }

    public function persistenceData(): array
    {
        return \App\Models\Experience::persistenceData($this->validated());
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($value));
    }
}
