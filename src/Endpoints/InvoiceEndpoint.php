<?php

declare(strict_types=1);

namespace FgoApi\Endpoints;

use FgoApi\Client;
use FgoApi\Exceptions\FgoApiException;
use FgoApi\Hash;
use FgoApi\Types\AddressClient;
use FgoApi\Types\AssociatedInvoice;
use FgoApi\Types\InvoiceLine;
use FgoApi\Types\InvoiceResult;
use FgoApi\Types\InvoiceStatusResult;
use InvalidArgumentException;

final class InvoiceEndpoint
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    /**
     * Create and emit a new invoice.
     *
     * @param array<InvoiceLine> $lines Invoice content lines (must be non-empty)
     */
    public function create(
        string $series,
        string $currency,
        string $invoiceType,
        AddressClient $clientData,
        array $lines,
        ?string $number = null,
        ?string $issueDate = null,
        ?string $dueDate = null,
        bool $checkDuplicate = false,
        bool $vatOnCollection = false,
    ): InvoiceResult {
        if ($lines === []) {
            throw new InvalidArgumentException('An invoice must contain at least one line.');
        }

        $payload = [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => Hash::forInvoiceCreate(
                $this->client->getCodUnic(),
                $this->client->getPrivateKey(),
                $clientData->name,
            ),
            'Serie' => $series,
            'Valuta' => $currency,
            'TipFactura' => $invoiceType,
            'Client' => $clientData->toArray(),
            'Continut' => \array_map(static fn(InvoiceLine $line) => $line->toArray(), $lines),
            'VerificareDuplicat' => $checkDuplicate,
        ];

        if ($number !== null) {
            $payload['Numar'] = $number;
        }
        if ($issueDate !== null) {
            $payload['DataEmitere'] = $issueDate;
        }
        if ($dueDate !== null) {
            $payload['DataScadenta'] = $dueDate;
        }
        if ($vatOnCollection) {
            $payload['TvaLaIncasare'] = true;
        }

        $response = $this->client->post('factura/emitere', $payload);

        return InvoiceResult::fromArray($this->extractInvoice($response));
    }

    public function print(string $number, string $series): InvoiceResult
    {
        $response = $this->client->post('factura/print', $this->invoiceOperationPayload($number, $series));

        return InvoiceResult::fromArray($this->extractInvoice($response));
    }

    public function delete(string $number, string $series): void
    {
        $this->client->post('factura/stergere', $this->invoiceOperationPayload($number, $series));
    }

    public function cancel(string $number, string $series): void
    {
        $this->client->post('factura/anulare', $this->invoiceOperationPayload($number, $series));
    }

    public function getStatus(string $number, string $series): InvoiceStatusResult
    {
        $response = $this->client->post('factura/getstatus', $this->invoiceOperationPayload($number, $series));

        return InvoiceStatusResult::fromArray($this->extractInvoice($response));
    }

    /**
     * Record a payment for an invoice.
     *
     * WARNING: Decommissioned for FGO Pro. Premium & Enterprise only.
     */
    public function addPayment(
        string $invoiceNumber,
        string $invoiceSeries,
        string $paymentType,
        float $amount,
        string $paymentDate,
    ): void {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $this->client->post('factura/incasare', [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => Hash::forInvoiceOperation(
                $this->client->getCodUnic(),
                $this->client->getPrivateKey(),
                $invoiceNumber,
            ),
            'NumarFactura' => $invoiceNumber,
            'SerieFactura' => $invoiceSeries,
            'TipIncasare' => $paymentType,
            'SumaIncasata' => $amount,
            'DataIncasare' => $paymentDate,
        ]);
    }

    public function deletePayment(string $invoiceNumber, string $invoiceSeries): void
    {
        $this->client->post('factura/stergereincasare', $this->invoiceOperationPayload($invoiceNumber, $invoiceSeries));
    }

    public function reverse(
        string $number,
        string $series,
        ?string $stornoSeries = null,
        ?string $stornoNumber = null,
        ?string $issueDate = null,
    ): void {
        $payload = $this->invoiceOperationPayload($number, $series);

        if ($stornoSeries !== null) {
            $payload['SerieStorno'] = $stornoSeries;
        }
        if ($stornoNumber !== null) {
            $payload['NumarStorno'] = $stornoNumber;
        }
        if ($issueDate !== null) {
            $payload['DataEmitere'] = $issueDate;
        }

        $this->client->post('factura/stornare', $payload);
    }

    public function addTrackingNumber(string $number, string $series, string $awb): void
    {
        $payload = $this->invoiceOperationPayload($number, $series);
        $payload['AWB'] = $awb;

        $this->client->post('factura/awb', $payload);
    }

    /**
     * List all invoices associated with a given invoice.
     *
     * ENTERPRISE plan only.
     *
     * @return array<AssociatedInvoice>
     */
    public function listAssociated(string $number, string $series): array
    {
        $response = $this->client->post('factura/listfacturiasociate', $this->invoiceOperationPayload($number, $series));

        $invoices = [];
        if (isset($response['Facturi']) && \is_array($response['Facturi'])) {
            foreach ($response['Facturi'] as $inv) {
                if (\is_array($inv)) {
                    $invoices[] = AssociatedInvoice::fromArray($inv);
                }
            }
        }

        return $invoices;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceOperationPayload(string $number, string $series): array
    {
        return [
            'CodUnic' => $this->client->getCodUnic(),
            'Hash' => Hash::forInvoiceOperation(
                $this->client->getCodUnic(),
                $this->client->getPrivateKey(),
                $number,
            ),
            'Numar' => $number,
            'Serie' => $series,
        ];
    }

    /**
     * @param  array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function extractInvoice(array $response): array
    {
        if (!isset($response['Factura']) || !\is_array($response['Factura'])) {
            throw new FgoApiException('API response did not contain a Factura object.');
        }

        return $response['Factura'];
    }
}
