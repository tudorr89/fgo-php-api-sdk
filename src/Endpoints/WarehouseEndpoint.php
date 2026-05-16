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
        $response = $this->client->post('articol/gestiune', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => Hash::forBase(
                $this->client->getCodUnic(),
                $this->client->getPrivateKey(),
            ),
        ]);

        $warehouses = [];
        if (isset($response['Result']['List']) && \is_array($response['Result']['List'])) {
            foreach ($response['Result']['List'] as $wh) {
                if (\is_array($wh)) {
                    $warehouses[] = Warehouse::fromArray($wh);
                }
            }
        }

        return $warehouses;
    }
}
