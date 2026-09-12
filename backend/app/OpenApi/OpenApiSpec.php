<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Yandex Maps Reviews API',
    description: 'Боевой путь: POST /api/organizations ставит Job в очередь и сразу отвечает 202. POST /api/organizations/parse — служебный sync для Swagger/отладки, в production выключен.',
)]
#[OA\Server(url: '/', description: 'Current host')]
#[OA\Tag(name: 'Organizations', description: 'Parse and read Yandex Maps organizations')]
class OpenApiSpec
{
}
