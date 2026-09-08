<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\EmailAllowlist;
use App\Support\NormalizedEmail;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já possui uma conta.',
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

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('email')) {
                    return;
                }

                $listed = EmailAllowlist::query()
                    ->where('email', $this->string('email')->toString())
                    ->exists();

                if (! $listed) {
                    $validator->errors()->add(
                        'email',
                        'Este e-mail não está autorizado a criar uma conta.',
                    );
                }
            },
        ];
    }
}
