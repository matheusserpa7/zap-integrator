<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $user instanceof User ? $user->currentWorkspace() : null;

        return $user instanceof User
            && $workspace instanceof Workspace
            && $user->can('create', [Instance::class, $workspace]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe um nome para a instância.',
            'name.max' => 'O nome da instância pode ter no máximo 80 caracteres.',
        ];
    }
}
