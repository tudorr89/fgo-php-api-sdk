<?php

declare(strict_types=1);

namespace FgoApi\Endpoints;

use FgoApi\Client;
use FgoApi\Hash;
use FgoApi\Types\Warehouse;

final class WarehouseEndpoint
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * List all warehouses configured in your account.
     *
     * @return array<Warehouse>
     */
    public function list(): array
    {
        $hash = Hash::forArticle(
            $this->client->getCodUnic(),
            $this->client->getPrivateKey(),
        );

        $response = $this->client->post('articol/gestiune', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => $hash,
        ]);

        $warehouses = [];
        if (isset($response['Result']['List']) && \is_array($response['Result']['List'])) {
            foreach ($response['Result']['List'] as $wh) {
                $warehouses[] = Warehouse::fromArray($wh);
            }
        }

        return $warehouses;
    }
}
