<?php

namespace App\Services\Yandex;

final class YandexMapsUrl
{
    public static function isValid(string $url): bool
    {
        return self::isSupportedMapsUrl($url);
    }

    public static function isOrganizationUrl(string $url): bool
    {
        return self::isValid($url) && self::businessId($url) !== null;
    }

    public static function businessId(string $url): ?string
    {
        if (! self::isSupportedMapsUrl($url)) {
            return null;
        }

        $decoded = urldecode($url);

        if (preg_match('/[?&#]oid=(\d+)/i', $decoded, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('#/org/(?:[^/]+/)*(\d+)#i', $decoded, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/businessId=(\d+)/i', $decoded, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private static function isSupportedMapsUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (preg_match('/(?:^|\.)yandex\.(ru|com)$/', $host) !== 1) {
            return false;
        }

        return str_starts_with($host, 'maps.')
            || str_contains($path, '/maps')
            || str_contains($path, '/org/');
    }
}
