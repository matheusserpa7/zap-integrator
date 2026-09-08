<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = CurrentWorkspace::from($this);

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
            'name.required' => 'The instance name is required.',
            'name.max' => 'The instance name may not be greater than 80 characters.',
        ];
    }
}
