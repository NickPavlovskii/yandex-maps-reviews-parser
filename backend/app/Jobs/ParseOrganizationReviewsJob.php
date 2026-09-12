<?php

namespace App\Jobs;

use App\Contracts\MapsParser;
use App\Enums\ParseRunStatus;
use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Services\Yandex\Exceptions\ParserBlockedException;
use App\Services\Yandex\Exceptions\ParserStructureChangedException;
use App\Services\Yandex\Exceptions\ParserTimeoutException;
use App\Services\Yandex\PersistParsedOrganization;
use App\Services\Yandex\YandexParserException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ParseOrganizationReviewsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 360;

    public function __construct(public readonly int $organizationId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(MapsParser $parser, PersistParsedOrganization $persister): void
    {
        $organization = Organization::query()->findOrFail($this->organizationId);

        $run = ParseRun::query()->create([
            'organization_id' => $organization->id,
            'status' => ParseRunStatus::Running,
            'started_at' => now(),
        ]);

        $organization->update(['parse_status' => ParseStatus::InProgress]);

        try {
            $parsed = $parser->parse($organization->url);

            $persister->persist($organization, $parsed);

            $run->update([
                'status' => ParseRunStatus::Success,
                'reviews_found' => count($parsed->reviews),
                'finished_at' => now(),
            ]);
        } catch (ParserStructureChangedException $exception) {
            $this->markRunFailed($run, $exception);
            $this->failWithoutRetry($exception);
        } catch (YandexParserException $exception) {
            $this->markRunFailed($run, $exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $organization = Organization::query()->find($this->organizationId);

        if ($organization === null) {
            return;
        }

        $organization->update([
            'parse_status' => $this->statusFromException($exception),
        ]);
    }

    private function markRunFailed(ParseRun $run, YandexParserException $exception): void
    {
        $run->update([
            'status' => ParseRunStatus::Failed,
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
    }

    private function failWithoutRetry(ParserStructureChangedException $exception): void
    {
        if ($this->job) {
            $this->fail($exception);

            return;
        }

        throw $exception;
    }

    private function statusFromException(?Throwable $exception): ParseStatus
    {
        return match (true) {
            $exception instanceof ParserBlockedException => ParseStatus::FailedBlocked,
            $exception instanceof ParserStructureChangedException => ParseStatus::FailedStructureChanged,
            $exception instanceof ParserTimeoutException => ParseStatus::FailedUnavailable,
            default => ParseStatus::FailedUnavailable,
        };
    }
}
