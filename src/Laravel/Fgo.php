<?php

declare(strict_types=1);

namespace FgoApi\Laravel;

use FgoApi\Client;
use FgoApi\Endpoints\ArticleEndpoint;
use FgoApi\Endpoints\InvoiceEndpoint;
use FgoApi\Endpoints\NomenclatureEndpoint;
use FgoApi\Endpoints\WarehouseEndpoint;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Client                default()
 * @method static Client                for(string $key)
 * @method static Client                make(array<string, mixed> $credentials)
 * @method static void                  forget(?string $key = null)
 * @method static InvoiceEndpoint       invoices()
 * @method static ArticleEndpoint       articles()
 * @method static NomenclatureEndpoint  nomenclatures()
 * @method static WarehouseEndpoint     warehouses()
 *
 * @see FgoManager
 */
class Fgo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FgoManager::class;
    }
}
