<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FgoApi\Client;
use FgoApi\Enums\Environment;
use FgoApi\Types\AddressClient;
use FgoApi\Types\InvoiceLine;

// --- Configuration ---
$codUnic = 'YOUR_CUI';
$privateKey = 'YOUR_PRIVATE_KEY';
$platformUrl = 'https://your-app.com';

// --- Create Client ---
$client = new Client(
    codUnic: $codUnic,
    privateKey: $privateKey,
    platformUrl: $platformUrl,
    environment: Environment::Test,
    timeout: 20,
);

// --- 1. List nomenclatures ---
echo "=== Invoice Types ===\n";
$types = $client->nomenclatures()->invoiceTypes();
foreach ($types as $type) {
    echo "  {$type->name}: {$type->value}\n";
}

echo "\n=== VAT Rates ===\n";
$vatRates = $client->nomenclatures()->vatRates();
foreach ($vatRates as $rate) {
    echo "  {$rate->name}: {$rate->value}\n";
}

// --- 2. Create an invoice ---
$clientData = new AddressClient(
    name: 'Ionescu Popescu',
    fiscalCode: '1234567890123',
    email: 'client@example.com',
    phone: '0712345678',
    country: 'RO',
    county: 'Bucuresti',
    locality: 'Sector 1',
    address: 'Str. Exemplu, Nr. 1, Bl. A, Sc. 1, Et. 2, Ap. 5',
    type: 'PF',
);

$lines = [
    new InvoiceLine(
        name: 'Servicii Consultanta',
        quantity: 2,
        unit: 'ORE',
        vatRate: 19,
        unitPrice: 150.00,
    ),
    new InvoiceLine(
        name: 'Dezvoltare Software',
        quantity: 1,
        unit: 'BUC',
        vatRate: 19,
        unitPrice: 2500.00,
    ),
];

try {
    $result = $client->invoices()->create(
        series: 'BV',
        currency: 'RON',
        invoiceType: 'Factura',
        clientData: $clientData,
        lines: $lines,
        number: null,
        issueDate: date('Y-m-d'),
    );

    echo "\n=== Invoice Created ===\n";
    echo "  Series: {$result->series}\n";
    echo "  Number: {$result->number}\n";
    echo "  PDF: {$result->pdfLink}\n";
    if ($result->paymentLink) {
        echo "  Payment: {$result->paymentLink}\n";
    }

    // --- 3. Get invoice status ---
    $status = $client->invoices()->getStatus($result->number, $result->series);
    echo "\n=== Invoice Status ===\n";
    echo "  Value: {$status->value}\n";
    echo "  Paid: {$status->paidValue}\n";

    // --- 4. Print invoice ---
    $printResult = $client->invoices()->print($result->number, $result->series);
    echo "\n=== Print Result ===\n";
    echo "  PDF Link: {$printResult->pdfLink}\n";

} catch (\FgoApi\Exceptions\FgoApiException $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}

// --- 5. List articles (Enterprise only) ---
try {
    $articles = $client->articles()->list(page: 1, perPage: 10);
    echo "\n=== Articles (Page 1, Total: {$articles->total}) ===\n";
    foreach ($articles->articles as $article) {
        echo "  {$article->name} - {$article->unitPrice} RON\n";
    }
} catch (\FgoApi\Exceptions\FgoApiException $e) {
    echo "\nArticles: " . $e->getMessage() . "\n";
}

// --- 6. List warehouses ---
try {
    $warehouses = $client->warehouses()->list();
    echo "\n=== Warehouses ===\n";
    foreach ($warehouses as $wh) {
        echo "  {$wh->code}: {$wh->name}\n";
    }
} catch (\FgoApi\Exceptions\FgoApiException $e) {
    echo "\nWarehouses: " . $e->getMessage() . "\n";
}
