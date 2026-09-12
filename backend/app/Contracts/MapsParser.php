<?php

namespace App\Contracts;

use App\Services\Yandex\DTO\ParsedOrganization;

interface MapsParser
{
    public function parse(string $url): ParsedOrganization;
}
