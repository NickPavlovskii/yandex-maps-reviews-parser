<?php

namespace Tests\Feature;

use App\Contracts\MapsParser;
use App\Enums\ParseRunStatus;
use App\Enums\ParseStatus;
use App\Jobs\ParseOrganizationReviewsJob;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Models\Review;
use App\Models\ReviewSnapshot;
use App\Services\Yandex\DTO\OrganizationData;
use App\Services\Yandex\DTO\ParsedOrganization;
use App\Services\Yandex\DTO\ReviewData;
use App\Services\Yandex\Exceptions\ParserBlockedException;
use App\Services\Yandex\Exceptions\ParserStructureChangedException;
use App\Services\Yandex\PersistParsedOrganization;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ParseOrganizationReviewsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_parser_results_without_duplicates(): void
    {
        $organization = Organization::factory()->create([
            'yandex_business_id' => '156355253662',
            'parse_status' => ParseStatus::Pending,
        ]);

        $this->mock(MapsParser::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('parse')
                ->twice()
                ->with($organization->url)
                ->andReturn($this->parsedOrganization());
        });

        $job = new ParseOrganizationReviewsJob($organization->id);
        $job->handle(app(MapsParser::class), app(PersistParsedOrganization::class));
        $job->handle(app(MapsParser::class), app(PersistParsedOrganization::class));

        $organization->refresh();

        $this->assertSame(ParseStatus::Success, $organization->parse_status);
        $this->assertSame('Цех', $organization->name);
        $this->assertSame(2, Review::query()->count());
        $this->assertSame(2, ReviewSnapshot::query()->count());
        $this->assertSame(2, ParseRun::query()->where('status', ParseRunStatus::Success)->count());

        $first = Review::query()->where('yandex_review_id', 'review-1')->first();
        $this->assertNotNull($first);
        $this->assertSame('Иван', $first->author);
        $this->assertSame(5, $first->rating);
        $this->assertSame('Отличное место', $first->text);
        $this->assertNull($first->business_reply);
        $this->assertSame('2026-08-20', $first->published_at?->toDateString());

        $this->assertDatabaseHas('reviews', [
            'yandex_review_id' => 'review-2',
            'author' => 'Анна',
            'text' => 'Хорошо',
            'business_reply' => 'Спасибо',
        ]);
    }

    public function test_it_fails_structure_changes_without_keeping_retry_status(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Pending,
        ]);

        $this->mock(MapsParser::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('parse')
                ->once()
                ->with($organization->url)
                ->andThrow(new ParserStructureChangedException('Yandex returned no fetchReviews responses.'));
        });

        $job = new ParseOrganizationReviewsJob($organization->id);
        $exception = new ParserStructureChangedException('Yandex returned no fetchReviews responses.');

        try {
            $job->handle(app(MapsParser::class), app(PersistParsedOrganization::class));
            $this->fail('Expected parser exception.');
        } catch (ParserStructureChangedException) {
            $this->assertSame(ParseStatus::InProgress, $organization->fresh()->parse_status);
        }

        $job->failed($exception);

        $this->assertSame(ParseStatus::FailedStructureChanged, $organization->fresh()->parse_status);
        $this->assertDatabaseHas('parse_runs', [
            'organization_id' => $organization->id,
            'status' => ParseRunStatus::Failed->value,
        ]);
    }

    public function test_it_fails_structure_changes_immediately_when_queued(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Pending,
        ]);

        $this->mock(MapsParser::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('parse')
                ->once()
                ->with($organization->url)
                ->andThrow(new ParserStructureChangedException('Yandex returned no fetchReviews responses.'));
        });

        $queuedJob = \Mockery::mock(QueueJob::class);
        $queuedJob->shouldReceive('fail')
            ->once()
            ->with(\Mockery::type(ParserStructureChangedException::class));

        $job = new ParseOrganizationReviewsJob($organization->id);
        $job->setJob($queuedJob);
        $job->handle(app(MapsParser::class), app(PersistParsedOrganization::class));

        $this->assertSame(ParseStatus::InProgress, $organization->fresh()->parse_status);
        $this->assertDatabaseHas('parse_runs', [
            'organization_id' => $organization->id,
            'status' => ParseRunStatus::Failed->value,
        ]);
    }

    public function test_it_keeps_in_progress_status_until_the_final_retry_fails(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Pending,
        ]);

        $this->mock(MapsParser::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('parse')
                ->once()
                ->with($organization->url)
                ->andThrow(new ParserBlockedException('Yandex returned 403.'));
        });

        $job = new ParseOrganizationReviewsJob($organization->id);

        try {
            $job->handle(app(MapsParser::class), app(PersistParsedOrganization::class));
            $this->fail('Expected parser exception.');
        } catch (ParserBlockedException) {
            $this->assertSame(ParseStatus::InProgress, $organization->fresh()->parse_status);
        }

        $job->failed(new ParserBlockedException('Yandex returned 403.'));

        $this->assertSame(ParseStatus::FailedBlocked, $organization->fresh()->parse_status);
    }

    private function parsedOrganization(): ParsedOrganization
    {
        return new ParsedOrganization(
            organization: new OrganizationData(
                businessId: '156355253662',
                name: 'Цех',
                rating: 4.8,
                ratingsCount: 701,
                reviewsCount: 623,
            ),
            reviews: [
                ReviewData::fromArray([
                    'yandexReviewId' => 'review-1',
                    'author' => 'Иван',
                    'rating' => 5,
                    'text' => 'Отличное место',
                    'businessReply' => null,
                    'publishedAt' => '2026-08-20',
                    'language' => 'ru',
                    'likes' => 0,
                    'dislikes' => 0,
                ]),
                ReviewData::fromArray([
                    'yandexReviewId' => 'review-2',
                    'author' => 'Анна',
                    'rating' => 4,
                    'text' => 'Хорошо',
                    'businessReply' => 'Спасибо',
                    'publishedAt' => '2026-08-19',
                    'language' => 'ru',
                    'likes' => 0,
                    'dislikes' => 0,
                ]),
            ],
            meta: [],
        );
    }
}
