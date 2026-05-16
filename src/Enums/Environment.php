<?php

declare(strict_types=1);

namespace FgoApi\Enums;

enum Environment: string
{
    case Test = 'https://api-testuat.fgo.ro/v1';
    case Production = 'https://api.fgo.ro/v1';
}
