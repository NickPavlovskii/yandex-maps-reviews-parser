<?php

namespace App\Rules;

use App\Services\Yandex\YandexMapsUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexMapsOrganizationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! YandexMapsUrl::isOrganizationUrl($value)) {
            $fail('Ссылка ведёт на поисковую выдачу, а не на карточку организации — не найден идентификатор.');
        }
    }
}
