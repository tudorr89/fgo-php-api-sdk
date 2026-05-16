<?php

declare(strict_types=1);

namespace FgoApi\Enums;

enum ClientType: string
{
    case Individual = 'PF';
    case LegalEntity = 'PJ';
}
