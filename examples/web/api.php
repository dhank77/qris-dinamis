<?php

declare(strict_types=1);

/**
 * JSON API
 *
 * POST /api.php
 *   action=validate&qris=...
 *   action=parse&qris=...
 *   action=convert&qris=...&amount=25000[&fee_type=fixed|percentage&fee_value=1000]
 *
 * Body boleh berupa form-urlencoded atau JSON.
 */

// Autoload: vendor Composer jika ada, jika tidak pakai autoloader mandiri.
require is_file(__DIR__ . '/../../vendor/autoload.php')
    ? __DIR__ . '/../../vendor/autoload.php'
    : __DIR__ . '/../../autoload.php';

use QrisDinamis\Qris;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$input = $_REQUEST;
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $json = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    }
}

$action = (string) ($input['action'] ?? '');
$qris = trim((string) ($input['qris'] ?? ''));

if (!in_array($action, ['validate', 'parse', 'convert'], true)) {
    respond(400, ['ok' => false, 'errors' => ['action harus validate, parse, atau convert']]);
}

$validation = Qris::validate($qris);

if ($action === 'validate') {
    respond(200, ['ok' => true, 'valid' => $validation['valid'], 'errors' => $validation['errors']]);
}

if (!$validation['valid']) {
    respond(422, ['ok' => false, 'errors' => $validation['errors']]);
}

if ($action === 'parse') {
    respond(200, ['ok' => true, 'data' => Qris::parse($qris)]);
}

// convert
$amount = (string) ($input['amount'] ?? '');
if (!ctype_digit($amount) || (int) $amount <= 0) {
    respond(422, ['ok' => false, 'errors' => ['Nominal tidak valid']]);
}

$feeType = $input['fee_type'] ?? null;
$feeValue = isset($input['fee_value']) && $input['fee_value'] !== '' ? (float) $input['fee_value'] : null;
if ($feeType === 'none' || $feeType === '') {
    $feeType = null;
}

try {
    $result = Qris::convert($qris, (int) $amount, $feeType, $feeValue);
} catch (\InvalidArgumentException $e) {
    respond(422, ['ok' => false, 'errors' => [$e->getMessage()]]);
}

respond(200, ['ok' => true, 'result' => $result, 'data' => Qris::parse($result)]);
