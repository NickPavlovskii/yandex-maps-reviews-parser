<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListOrganizationReviewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'sort' => ['sometimes', 'in:newest,oldest'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 50);
    }

    public function sort(): string
    {
        return $this->input('sort', 'newest');
    }
}
