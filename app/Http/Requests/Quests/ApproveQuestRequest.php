<?php

declare(strict_types=1);

namespace App\Http\Requests\Quests;

use App\Enums\Difficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveQuestRequest extends FormRequest
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
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
        ];
    }
}
