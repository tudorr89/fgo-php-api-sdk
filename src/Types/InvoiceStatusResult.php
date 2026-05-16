<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class InvoiceStatusResult
{
    public function __construct(
        public string $number,
        public string $series,
        public float $value,
        public float $paidValue,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            number: $data['Numar'],
            series: $data['Serie'],
            value: (float) $data['Valoare'],
            paidValue: (float) $data['ValoareAchitata'],
        );
    }
}
