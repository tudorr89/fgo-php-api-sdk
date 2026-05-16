<?php

declare(strict_types=1);

namespace FgoApi\Types;

readonly class InvoiceResult
{
    /**
     * @param array<StockInfo> $stockInfo
     */
    public function __construct(
        public string $number,
        public string $series,
        public string $pdfLink,
        public ?string $paymentLink = null,
        public array $stockInfo = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $stockInfo = [];
        if (isset($data['InfoStoc']) && is_array($data['InfoStoc'])) {
            foreach ($data['InfoStoc'] as $stock) {
                $stockInfo[] = StockInfo::fromArray($stock);
            }
        }

        return new self(
            number: $data['Numar'],
            series: $data['Serie'],
            pdfLink: $data['Link'],
            paymentLink: $data['LinkPlata'] ?? null,
            stockInfo: $stockInfo,
        );
    }
}
