<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SendTextMessageRequest extends FormRequest
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
            'instance_id' => ['required', 'string', 'max:64'],
            'to' => ['required', 'string', 'max:32'],
            'text' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'instance_id.required' => 'The instance id is required.',
            'to.required' => 'The destination number is required.',
            'text.required' => 'The message text is required.',
        ];
    }
}
