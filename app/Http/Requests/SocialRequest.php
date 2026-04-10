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
        $platform = $this->input('plataforma', $this->input('nombre_plataforma', $this->input('platform')));
        $url = $this->input('url', $this->input('url_plataforma'));
        $cvUrl = $this->input('cv_url', $this->input('url_cv', $this->input('cvUrl')));

        $this->merge([
            'plataforma' => $this->normalizePlatform($platform),
            'url' => is_string($url) ? trim($url) : $url,
            'cv_url' => is_string($cvUrl) ? trim($cvUrl) : $cvUrl,
        ]);
    }

    public function rules(): array
    {
        return [
            'plataforma' => ['nullable', 'in:linkedin,github,gitlab,facebook,instagram,x,youtube,portafolio,otro', 'required_with:url'],
            'url' => ['nullable', 'url', 'max:255', 'required_with:plataforma'],
            'cv_url' => ['nullable', 'string', 'max:255'],
            'cvFile' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasPlatformUrl = filled($this->input('plataforma')) || filled($this->input('url'));
            $hasCv = filled($this->input('cv_url')) || $this->hasFile('cvFile') || $this->hasFile('cv_file');

            if (! $hasPlatformUrl && ! $hasCv) {
                $validator->errors()->add('plataforma', 'Debes enviar al menos un enlace social o un CV.');
            }
        });
    }

    public function persistenceData(): array
    {
        return $this->validated();
    }

    private function normalizePlatform(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $platform = mb_strtolower(trim($value));

        return match ($platform) {
            'portfolio' => 'portafolio',
            'twitter' => 'x',
            default => $platform,
        };
    }
}
