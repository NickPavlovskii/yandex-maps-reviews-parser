<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MapsParser;
use App\Enums\ParseRunStatus;
use App\Enums\ParseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListOrganizationReviewsRequest;
use App\Http\Requests\ParseYandexRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\ReviewResource;
use App\Jobs\ParseOrganizationReviewsJob;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Services\Yandex\PersistParsedOrganization;
use App\Services\Yandex\YandexParserException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly MapsParser $parser,
        private readonly PersistParsedOrganization $persister,
    ) {}

    #[OA\Post(
        path: '/api/organizations/parse',
        summary: 'Вставить ссылку, спарсить и увидеть то, что уйдёт в БД',
        description: 'Синхронно открывает карточку в Playwright, сохраняет организацию и отзывы в БД и возвращает записанный результат. Запрос может идти 1–3 минуты.',
        tags: ['Organizations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/YandexMapsUrlRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Организация и отзывы после записи в БД',
                content: new OA\JsonContent(ref: '#/components/schemas/ParseDbResult'),
            ),
            new OA\Response(response: 422, description: 'Невалидная ссылка или парсер не смог получить отзывы'),
        ],
    )]
    public function parse(ParseYandexRequest $request): JsonResponse
    {
        set_time_limit(360);

        $organization = $this->findOrCreateOrganization($request);
        $run = ParseRun::query()->create([
            'organization_id' => $organization->id,
            'status' => ParseRunStatus::Running,
            'started_at' => now(),
        ]);

        $organization->update(['parse_status' => ParseStatus::InProgress]);

        try {
            $parsed = $this->parser->parse($request->mapsUrl());
            $this->persister->persist($organization, $parsed);
        } catch (YandexParserException $exception) {
            report($exception);

            $run->update([
                'status' => ParseRunStatus::Failed,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $organization->update(['parse_status' => ParseStatus::FailedUnavailable]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to parse Yandex Maps organization.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        $organization->refresh();
        $reviews = $organization->reviews()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(50);

        $run->update([
            'status' => ParseRunStatus::Success,
            'reviews_found' => $reviews->total(),
            'finished_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => OrganizationResource::make($organization)->resolve(),
                'reviews' => ReviewResource::collection($reviews->items())->resolve(),
                'pagination' => [
                    'page' => $reviews->currentPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'last_page' => $reviews->lastPage(),
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/organizations',
        summary: 'Только поставить URL в очередь',
        tags: ['Organizations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['url'],
                properties: [
                    new OA\Property(
                        property: 'url',
                        type: 'string',
                        example: 'https://yandex.ru/maps/org/156355253662',
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 202, description: 'Organization saved and parse job queued'),
            new OA\Response(response: 422, description: 'Invalid Yandex Maps URL'),
        ],
    )]
    public function store(ParseYandexRequest $request): JsonResponse
    {
        $organization = $this->findOrCreateOrganization($request);

        if ($organization->parse_status !== ParseStatus::InProgress) {
            ParseOrganizationReviewsJob::dispatch($organization->id);
        }

        return OrganizationResource::make($organization->fresh())
            ->response()
            ->setStatusCode(202);
    }

    #[OA\Get(
        path: '/api/organizations/{id}',
        summary: 'Get organization and parse status',
        tags: ['Organizations'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Organization from local database'),
            new OA\Response(response: 404, description: 'Organization not found'),
        ],
    )]
    public function show(Organization $organization): OrganizationResource
    {
        return OrganizationResource::make($organization);
    }

    #[OA\Get(
        path: '/api/organizations/{id}/reviews',
        summary: 'List stored reviews',
        tags: ['Organizations'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 50),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated reviews from local database'),
            new OA\Response(response: 404, description: 'Organization not found'),
        ],
    )]
    public function reviews(
        ListOrganizationReviewsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $reviews = $organization->reviews()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ReviewResource::collection($reviews);
    }

    private function findOrCreateOrganization(ParseYandexRequest $request): Organization
    {
        return Organization::query()->firstOrCreate(
            ['yandex_business_id' => $request->businessId()],
            [
                'url' => $request->mapsUrl(),
                'parse_status' => ParseStatus::Pending,
            ],
        );
    }
}
