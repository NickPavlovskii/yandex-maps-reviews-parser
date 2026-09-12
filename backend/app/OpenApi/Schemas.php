<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'YandexMapsUrlRequest',
    required: ['url'],
    properties: [
        new OA\Property(
            property: 'url',
            description: 'Любая ссылка на карточку организации в Яндекс.Картах',
            type: 'string',
            example: 'https://yandex.ru/maps/org/156355253662',
        ),
    ],
)]
#[OA\Schema(
    schema: 'Organization',
    description: 'Запись организации, которая сохраняется в таблице organizations',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'url', type: 'string', example: 'https://yandex.ru/maps/org/156355253662'),
        new OA\Property(property: 'yandex_business_id', type: 'string', example: '156355253662'),
        new OA\Property(property: 'name', type: 'string', example: 'Цех'),
        new OA\Property(property: 'avg_rating', type: 'number', format: 'float', example: 4.8),
        new OA\Property(property: 'ratings_count', type: 'integer', example: 701),
        new OA\Property(property: 'reviews_count', type: 'integer', example: 623),
        new OA\Property(property: 'parse_status', type: 'string', example: 'success'),
        new OA\Property(property: 'last_parsed_at', type: 'string', example: '2026-09-11T20:00:00+00:00'),
    ],
)]
#[OA\Schema(
    schema: 'Review',
    description: 'Запись отзыва, которая сохраняется в таблице reviews',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'yandex_review_id', type: 'string', example: 'LvDp2rjgX9Z8-EKH7rMr6QGee7D1FDgH'),
        new OA\Property(property: 'author', type: 'string', example: 'Иван'),
        new OA\Property(property: 'rating', type: 'integer', example: 5),
        new OA\Property(property: 'text', type: 'string', example: 'Отличное место'),
        new OA\Property(property: 'business_reply', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'published_at', type: 'string', example: '2026-08-20'),
    ],
)]
#[OA\Schema(
    schema: 'ParseDbResult',
    description: 'Данные, которые парсер записывает в БД. В reviews — первая страница из 50.',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'organization', ref: '#/components/schemas/Organization'),
                new OA\Property(
                    property: 'reviews',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Review'),
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'per_page', type: 'integer', example: 50),
                        new OA\Property(property: 'total', type: 'integer', example: 623),
                        new OA\Property(property: 'last_page', type: 'integer', example: 13),
                    ],
                    type: 'object',
                ),
            ],
            type: 'object',
        ),
    ],
)]
class Schemas
{
}
