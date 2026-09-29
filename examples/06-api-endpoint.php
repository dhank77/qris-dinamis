<?php

/**
 * Contoh 6 — Endpoint JSON minimal (PHP murni).
 *
 * Jalankan:
 *   php -S localhost:8000 examples/06-api-endpoint.php
 *
 * Coba:
 *   curl -X POST localhost:8000 -d amount=25000
 *   curl -X POST localhost:8000 -d amount=25000 -d fee_type=fixed -d fee_value=1000
 */

require __DIR__ . '/_bootstrap.php';

use QrisDinamis\Exception\InvalidQrisException;
use QrisDinamis\Qris;

header('Content-Type: application/json');

// QRIS statis toko Anda — simpan di .env / config, jangan diterima dari klien
$merchantQris = getenv('QRIS_STATIC') ?: SAMPLE_QRIS;

$amount   = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT) ?: 0;
$feeType  = $_POST['fee_type'] ?? null;
$feeValue = isset($_POST['fee_value']) ? (float) $_POST['fee_value'] : null;

try {
    $qris = Qris::convert($merchantQris, $amount, $feeType, $feeValue);

    echo json_encode([
        'ok'     => true,
        'amount' => $amount,
        'qris'   => $qris,
    ]);
} catch (InvalidQrisException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $e->getErrors()]);
}
