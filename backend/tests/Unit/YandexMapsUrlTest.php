<?php

namespace Tests\Unit;

use App\Services\Yandex\YandexMapsUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YandexMapsUrlTest extends TestCase
{
    #[DataProvider('organizationUrls')]
    public function test_it_extracts_business_id_from_any_organization_url(string $url, string $businessId): void
    {
        $this->assertTrue(YandexMapsUrl::isValid($url));
        $this->assertTrue(YandexMapsUrl::isOrganizationUrl($url));
        $this->assertSame($businessId, YandexMapsUrl::businessId($url));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function organizationUrls(): array
    {
        return [
            'short org id' => [
                'https://yandex.ru/maps/org/156355253662',
                '156355253662',
            ],
            'org slug and id' => [
                'https://yandex.ru/maps/org/tsekh/156355253662',
                '156355253662',
            ],
            'org reviews tab' => [
                'https://yandex.ru/maps/org/tsekh/156355253662/reviews',
                '156355253662',
            ],
            'maps subdomain' => [
                'https://maps.yandex.ru/org/156355253662',
                '156355253662',
            ],
            'yandex.com' => [
                'https://yandex.com/maps/org/156355253662',
                '156355253662',
            ],
            'query oid' => [
                'https://yandex.ru/maps/?ol=biz&oid=156355253662',
                '156355253662',
            ],
            'encoded poi uri' => [
                'https://yandex.ru/maps/970/novorossiysk/?ll=37.783460%2C44.713693&mode=poi&poi%5Bpoint%5D=37.781069%2C44.714909&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D156355253662&tab=reviews&z=15.93',
                '156355253662',
            ],
            'businessId query' => [
                'https://yandex.ru/maps/org/tsekh/reviews/?businessId=156355253662',
                '156355253662',
            ],
            'another organization' => [
                'https://yandex.ru/maps/org/gum/1074720998/reviews',
                '1074720998',
            ],
        ];
    }

    #[DataProvider('mapsUrlsWithoutBusinessId')]
    public function test_it_does_not_treat_city_or_share_urls_as_organization_links(string $url): void
    {
        $this->assertTrue(YandexMapsUrl::isValid($url));
        $this->assertFalse(YandexMapsUrl::isOrganizationUrl($url));
        $this->assertNull(YandexMapsUrl::businessId($url));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function mapsUrlsWithoutBusinessId(): array
    {
        return [
            'city map' => ['https://yandex.ru/maps/970/novorossiysk'],
            'short share link' => ['https://yandex.ru/maps/-/CHHD5XYs'],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function test_it_rejects_urls_that_are_not_yandex_maps(string $url): void
    {
        $this->assertFalse(YandexMapsUrl::isValid($url));
        $this->assertNull(YandexMapsUrl::businessId($url));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'empty' => [''],
            'not a url' => ['not-a-url'],
            'other host' => ['https://google.com/maps/org/156355253662'],
            'yandex without maps' => ['https://yandex.ru/search/?text=cafe'],
        ];
    }
}
