<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ApiAbility;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = CurrentWorkspace::from($this);

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('create', [PersonalAccessToken::class, $workspace]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'distinct', Rule::in(ApiAbility::values())],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe um nome para a chave.',
            'name.max' => 'O nome da chave pode ter no máximo 80 caracteres.',
            'abilities.required' => 'Selecione pelo menos uma permissão.',
            'abilities.min' => 'Selecione pelo menos uma permissão.',
            'abilities.*.in' => 'Uma das permissões selecionadas é inválida.',
            'expires_at.date' => 'Informe uma data de expiração válida.',
            'expires_at.after' => 'A expiração deve ser uma data futura.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('expires_at') === '') {
            $this->merge(['expires_at' => null]);
        }
    }

    /**
     * @return list<string>
     */
    public function abilities(): array
    {
        /** @var list<string> $abilities */
        $abilities = array_values($this->validated('abilities'));

        return $abilities;
    }
}
