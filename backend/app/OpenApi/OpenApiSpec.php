<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Yandex Maps Reviews API',
    description: 'В Swagger используйте POST /api/organizations/parse: вставьте ссылку на карточку Яндекс.Карт и получите организацию и отзывы в том виде, в каком они сохраняются в БД.',
)]
#[OA\Server(url: '/', description: 'Current host')]
#[OA\Tag(name: 'Organizations', description: 'Parse and read Yandex Maps organizations')]
class OpenApiSpec
{
}
