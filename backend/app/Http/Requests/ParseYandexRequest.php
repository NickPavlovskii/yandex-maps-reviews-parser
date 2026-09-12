<?php

namespace App\Http\Requests;

use App\Rules\YandexMapsOrganizationUrl;
use App\Services\Yandex\YandexMapsUrl;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

class ParseYandexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', new YandexMapsOrganizationUrl],
        ];
    }

    public function mapsUrl(): string
    {
        return $this->string('url')->toString();
    }

    public function businessId(): string
    {
        $businessId = YandexMapsUrl::businessId($this->mapsUrl());

        if ($businessId === null) {
            throw new LogicException('Validated Yandex Maps URL must contain a business id.');
        }

        return $businessId;
    }
}
