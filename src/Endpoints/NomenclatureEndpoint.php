<?php

declare(strict_types=1);

namespace FgoApi\Endpoints;

use FgoApi\Client;
use FgoApi\Enums\NomenclatureType;
use FgoApi\Hash;
use FgoApi\Types\NomenclatureItem;

final class NomenclatureEndpoint
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * Retrieve a nomenclature list by type.
     *
     * @return array<NomenclatureItem>
     */
    public function get(NomenclatureType $type, ?string $countyCode = null): array
    {
        $endpoint = 'nomenclator/' . $type->value;

        if ($countyCode !== null) {
            $endpoint .= '?judet=' . \urlencode($countyCode);
        }

        $response = $this->client->post($endpoint, [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => Hash::forBase(
                $this->client->getCodUnic(),
                $this->client->getPrivateKey(),
            ),
        ]);

        $items = [];
        if (isset($response['List']) && \is_array($response['List'])) {
            foreach ($response['List'] as $item) {
                if (\is_array($item)) {
                    $items[] = NomenclatureItem::fromArray($item);
                }
            }
        }

        return $items;
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function countries(): array
    {
        return $this->get(NomenclatureType::Countries);
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function counties(): array
    {
        return $this->get(NomenclatureType::Counties);
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function vatRates(): array
    {
        return $this->get(NomenclatureType::VatRates);
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function banks(): array
    {
        return $this->get(NomenclatureType::Banks);
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function paymentTypes(): array
    {
        return $this->get(NomenclatureType::PaymentTypes);
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function invoiceTypes(): array
    {
        return $this->get(NomenclatureType::InvoiceTypes);
    }

    /**
     * @return array<NomenclatureItem>
     */
    public function clientTypes(): array
    {
        return $this->get(NomenclatureType::ClientTypes);
    }

    /**
     * Get localities filtered by county code.
     *
     * @return array<NomenclatureItem>
     */
    public function localities(string $countyCode): array
    {
        return $this->get(NomenclatureType::Localities, $countyCode);
    }
}
