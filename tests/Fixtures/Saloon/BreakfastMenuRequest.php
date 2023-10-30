<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Tests\Fixtures\Saloon;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;

class BreakfastMenuRequest extends SoloRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'https://tests.saloon.dev/api/breakfast-menu';
    }
}
