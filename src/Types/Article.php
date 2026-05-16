<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class Article
{
    public function __construct(
        public string $name,
        public float $unitPrice,
        public ?string $unit = null,
        public ?string $accountCode = null,
        public ?float $vatRate = null,
        public ?float $stock = null,
        public ?string $barcode = null,
        public ?string $lastUsed = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['Nume'],
            unitPrice: (float) ($data['PretUnitar'] ?? 0),
            unit: $data['UM'] ?? null,
            accountCode: $data['CodConta'] ?? null,
            vatRate: isset($data['CotaTva']) ? (float) $data['CotaTva'] : null,
            stock: isset($data['Stoc']) ? (float) $data['Stoc'] : null,
            barcode: $data['CodBare'] ?? null,
            lastUsed: $data['UltimaUtilizare'] ?? null,
        );
    }
}
