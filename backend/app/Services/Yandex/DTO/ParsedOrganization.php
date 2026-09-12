<?php

namespace App\Services\Yandex\DTO;

readonly class ParsedOrganization
{
    /**
     * @param  list<ReviewData>  $reviews
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public OrganizationData $organization,
        public array $reviews,
        public array $meta,
    ) {}
}
