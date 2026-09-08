<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Models\EmailAllowlist;
use App\Support\NormalizedEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllowlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmailAllowlist::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('email_allowlists', 'email')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está na lista de acesso.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => NormalizedEmail::make($this->string('email')->toString()),
            ]);
        }
    }
}
