<?php

declare(strict_types=1);

namespace App\Http\Requests\Quests;

use App\Enums\Category;
use App\Enums\Difficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Post and edit share these rules. Who may edit is decided by QuestPolicy in the controller.
 */
class StoreQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Blank objective rows are dropped, so the form can keep an empty row open.
     */
    protected function prepareForValidation(): void
    {
        $objectives = $this->input('objectives');

        if (is_array($objectives)) {
            $this->merge([
                'objectives' => array_values(array_filter(
                    $objectives,
                    // Empty strings arrive as null (ConvertEmptyStringsToNull).
                    fn (mixed $objective): bool => $objective !== null
                        && (! is_string($objective) || trim($objective) !== ''),
                )),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:60'],
            'summary' => ['required', 'string', 'max:90'],
            'category' => ['required', Rule::enum(Category::class)],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'brief' => ['required', 'string', 'max:2000'],
            'objectives' => ['required', 'array', 'min:1', 'max:8'],
            'objectives.*' => ['required', 'string', 'max:120'],
            'proof' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Give your quest a title first.',
        ];
    }
}
