<?php

namespace App\Services\Yandex;

use App\Contracts\MapsParser;
use App\Services\Yandex\DTO\OrganizationData;
use App\Services\Yandex\DTO\ParsedOrganization;
use App\Services\Yandex\DTO\ReviewData;
use App\Services\Yandex\Exceptions\ParserBlockedException;
use App\Services\Yandex\Exceptions\ParserStructureChangedException;
use App\Services\Yandex\Exceptions\ParserTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class YandexMapsParser implements MapsParser
{
    public function parse(string $url): ParsedOrganization
    {
        $timeout = (int) config('parser.timeout');

        Log::info('parsing_started', ['url' => $url]);

        $parserUrl = config('parser.url');

        if (! $parserUrl && app()->isProduction()) {
            Log::error('parsing_failed', [
                'url' => $url,
                'error' => 'PARSER_URL is not configured',
            ]);

            throw new YandexParserException(
                'PARSER_URL is not configured; production cannot run local node.',
            );
        }

        $output = $parserUrl
            ? $this->parseViaService($url, $timeout)
            : $this->parseViaProcess($url, $timeout);

        $payload = json_decode($output, true);

        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            Log::error('parsing_failed', [
                'url' => $url,
                'output' => $output,
            ]);

            throw $this->exceptionFromOutput($output, '');
        }

        $reviews = array_map(
            static fn (array $review): ReviewData => ReviewData::fromArray($review),
            $payload['reviews'] ?? [],
        );

        Log::info('parsing_completed', [
            'url' => $url,
            'reviews' => count($reviews),
        ]);

        return new ParsedOrganization(
            organization: OrganizationData::fromArray($payload['organization'] ?? []),
            reviews: $reviews,
            meta: $payload['meta'] ?? [],
        );
    }

    private function parseViaService(string $url, int $timeout): string
    {
        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->acceptJson()
                ->post(rtrim((string) config('parser.url'), '/').'/parse', [
                    'url' => $url,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('parsing_failed', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            if (str_contains(strtolower($exception->getMessage()), 'timed out')) {
                throw new ParserTimeoutException(
                    'Yandex parser timed out.',
                    previous: $exception,
                );
            }

            throw new YandexParserException(
                'Parser service is unavailable: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            Log::error('parsing_failed', [
                'url' => $url,
                'output' => $response->body(),
            ]);

            throw $this->exceptionFromOutput($response->body(), '');
        }

        return $response->body();
    }

    private function parseViaProcess(string $url, int $timeout): string
    {
        $script = config('parser.script');

        try {
            $result = Process::timeout($timeout)
                ->path(dirname($script))
                ->run([
                    config('parser.node'),
                    $script,
                    $url,
                ]);
        } catch (ProcessTimedOutException $exception) {
            Log::error('parsing_failed', [
                'url' => $url,
                'error' => 'timeout',
            ]);

            throw new ParserTimeoutException(
                'Yandex parser timed out.',
                previous: $exception,
            );
        }

        if ($result->failed()) {
            Log::error('parsing_failed', [
                'url' => $url,
                'stderr' => $result->errorOutput(),
            ]);

            throw $this->exceptionFromOutput($result->output(), $result->errorOutput());
        }

        return $result->output();
    }

    private function exceptionFromOutput(string $stdout, string $stderr): YandexParserException
    {
        $payload = json_decode($stdout, true);
        $code = is_array($payload)
            ? ($payload['error']['code'] ?? $payload['error'] ?? null)
            : null;
        $message = $this->errorMessageFromOutput($stdout, $stderr);

        return match (true) {
            $code === 'STRUCTURE_CHANGED',
            str_contains($message, 'fetchReviews'),
            str_contains($message, 'parser format has changed') => new ParserStructureChangedException($message),
            $code === 'BLOCKED',
            str_contains(strtolower($message), 'captcha'),
            str_contains($message, '403') => new ParserBlockedException($message),
            $code === 'TIMEOUT' => new ParserTimeoutException($message),
            default => new YandexParserException($message),
        };
    }

    private function errorMessageFromOutput(string $stdout, string $stderr): string
    {
        $payload = json_decode($stdout, true);

        if (is_array($payload)) {
            $message = $payload['error']['message'] ?? (is_string($payload['error'] ?? null) ? $payload['error'] : null);
            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        $stderr = trim($stderr);

        return $stderr !== ''
            ? $stderr
            : 'Yandex parser failed with an unknown error.';
    }
}
