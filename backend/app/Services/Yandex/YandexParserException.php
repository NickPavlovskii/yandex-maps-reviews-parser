<?php

namespace App\Services\Yandex;

use App\Enums\ParseStatus;
use App\Services\Yandex\Exceptions\ParserBlockedException;
use App\Services\Yandex\Exceptions\ParserStructureChangedException;
use App\Services\Yandex\Exceptions\ParserTimeoutException;
use RuntimeException;
use Throwable;

class YandexParserException extends RuntimeException
{
    public static function parseStatus(?Throwable $exception): ParseStatus
    {
        return match (true) {
            $exception instanceof ParserBlockedException => ParseStatus::FailedBlocked,
            $exception instanceof ParserStructureChangedException => ParseStatus::FailedStructureChanged,
            $exception instanceof ParserTimeoutException => ParseStatus::FailedUnavailable,
            default => ParseStatus::FailedUnavailable,
        };
    }
}
