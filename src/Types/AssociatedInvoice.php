<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class AssociatedInvoice
{
    public function __construct(
        public string $number,
        public string $series,
        public float $value,
        public float $paidValue,
        public string $issueDate,
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
            issueDate: $data['DataEmitere'],
        );
    }
}
