<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $platform = $this->input('nombre_plataforma', $this->input('platform'));
        $url = $this->input('url_plataforma', $this->input('url'));
        $cvUrl = $this->input('url_cv', $this->input('cvUrl'));

        $this->merge([
            'nombre_plataforma' => $this->normalizeText($platform),
            'url_plataforma' => is_string($url) ? trim($url) : $url,
            'url_cv' => is_string($cvUrl) ? trim($cvUrl) : $cvUrl,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre_plataforma' => ['nullable', 'string', 'min:2', 'max:50', 'required_with:url_plataforma'],
            'url_plataforma' => ['nullable', 'url', 'max:255', 'required_with:nombre_plataforma'],
            'url_cv' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }

                    $looksLikeUrl = filter_var($value, FILTER_VALIDATE_URL);
                    $looksLikeStoredPdf = preg_match('/(^cv\/.+\.pdf$)|(^storage\/.+\.pdf$)|(^.+\.pdf$)/i', $value);

                    if (!$looksLikeUrl && !$looksLikeStoredPdf) {
                        $fail('El CV debe ser un enlace valido o una referencia PDF valida.');
                    }
                },
            ],
            'cvFile' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasPlatformUrl = filled($this->input('nombre_plataforma')) || filled($this->input('url_plataforma'));
            $hasCv = filled($this->input('url_cv')) || $this->hasFile('cvFile') || $this->hasFile('cv_file');

            if (!$hasPlatformUrl && !$hasCv) {
                $validator->errors()->add('nombre_plataforma', 'Debes enviar al menos un enlace social o un CV.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre_plataforma.required_with' => 'La plataforma es obligatoria cuando envias un enlace.',
            'url_plataforma.required_with' => 'El enlace es obligatorio cuando eliges una plataforma.',
            'url_plataforma.url' => 'El enlace social debe ser un URL valido.',
            'cvFile.mimes' => 'El archivo del CV debe ser un PDF.',
            'cv_file.mimes' => 'El archivo del CV debe ser un PDF.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_plataforma' => 'plataforma',
            'url_plataforma' => 'enlace social',
            'url_cv' => 'URL del CV',
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
