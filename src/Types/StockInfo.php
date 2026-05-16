<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class StockInfo
{
    public function __construct(
        public string $accountCode,
        public string $name,
        public float $stock,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountCode: $data['CodConta'],
            name: $data['Nume'],
            stock: (float) $data['Stoc'],
        );
    }
}
