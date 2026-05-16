<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class InvoiceLine
{
    public function __construct(
        public string $name,
        public float $quantity,
        public string $unit,
        public float $vatRate,
        public ?float $unitPrice = null,
        public ?float $totalPrice = null,
        public ?string $description = null,
        public ?string $articleCode = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'Denumire' => $this->name,
            'NrProduse' => $this->quantity,
            'UM' => $this->unit,
            'CotaTVA' => $this->vatRate,
        ];

        if ($this->unitPrice !== null) {
            $data['PretUnitar'] = $this->unitPrice;
        }
        if ($this->totalPrice !== null) {
            $data['PretTotal'] = $this->totalPrice;
        }
        if ($this->description !== null) {
            $data['Descriere'] = $this->description;
        }
        if ($this->articleCode !== null) {
            $data['CodArticol'] = $this->articleCode;
        }

        return $data;
    }
}
