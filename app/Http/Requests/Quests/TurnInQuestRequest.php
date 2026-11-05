<?php

declare(strict_types=1);

namespace App\Http\Requests\Quests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TurnInQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'result_url' => ['nullable', 'required_without:result_note', 'url:http,https', 'max:2048'],
            'result_note' => ['nullable', 'required_without:result_url', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'result_url.required_without' => 'Add a link to your result or a note for the lead.',
            'result_note.required_without' => 'Add a link to your result or a note for the lead.',
        ];
    }
}
