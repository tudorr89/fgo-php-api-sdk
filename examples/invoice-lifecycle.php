<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FgoApi\Client;
use FgoApi\Enums\Environment;
use FgoApi\Types\AddressClient;
use FgoApi\Types\InvoiceLine;

/*
 * Advanced example demonstrating all invoice lifecycle operations:
 * create → get status → add payment → print → cancel → reverse → add tracking
 */

$codUnic = 'YOUR_CUI';
$privateKey = 'YOUR_PRIVATE_KEY';
$platformUrl = 'https://your-app.com';

$client = new Client($codUnic, $privateKey, $platformUrl, Environment::Test);

// 1. Create an invoice
$clientData = new AddressClient(
    name: 'SC Example SRL',
    fiscalCode: 'RO12345678',
    country: 'RO',
    county: 'Cluj',
    locality: 'Cluj-Napoca',
    type: 'PJ',
);

$lines = [
    new InvoiceLine(
        name: 'Echipament IT',
        quantity: 1,
        unit: 'BUC',
        vatRate: 19,
        unitPrice: 5000.00,
    ),
];

$invoice = $client->invoices()->create(
    series: 'IS',
    currency: 'RON',
    invoiceType: 'Factura',
    clientData: $clientData,
    lines: $lines,
);

echo "Created: {$invoice->series}{$invoice->number}\n";
echo "PDF: {$invoice->pdfLink}\n";

// 2. Get status
$status = $client->invoices()->getStatus($invoice->number, $invoice->series);
echo "Status - Value: {$status->value}, Paid: {$status->paidValue}\n";

// 3. Add a payment (Premium/Enterprise only)
try {
    $client->invoices()->addPayment(
        invoiceNumber: $invoice->number,
        invoiceSeries: $invoice->series,
        paymentType: 'OP',
        amount: 5950.00,
        paymentDate: date('Y-m-d H:i:s'),
    );
    echo "Payment added\n";
} catch (\FgoApi\Exceptions\FgoApiException $e) {
    echo "Payment: {$e->getMessage()}\n";
}

// 4. Print PDF
$printed = $client->invoices()->print($invoice->number, $invoice->series);
echo "Printed: {$printed->pdfLink}\n";

// 5. Add tracking number
try {
    $client->invoices()->addTrackingNumber(
        number: $invoice->number,
        series: $invoice->series,
        awb: 'FAN1234567890',
    );
    echo "Tracking added\n";
} catch (\FgoApi\Exceptions\FgoApiException $e) {
    echo "Tracking: {$e->getMessage()}\n";
}

// 6. Cancel invoice
// $client->invoices()->cancel($invoice->number, $invoice->series);
// echo "Cancelled\n";

// 7. Delete invoice
// $client->invoices()->delete($invoice->number, $invoice->series);
// echo "Deleted\n";
