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
 * @method static InvoiceEndpoint      invoices()
 * @method static ArticleEndpoint      articles()
 * @method static NomenclatureEndpoint nomenclatures()
 * @method static WarehouseEndpoint    warehouses()
 * @method static string               getCodUnic()
 * @method static string               getPrivateKey()
 * @method static string               getPlatformUrl()
 *
 * @see Client
 */
class Fgo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
