<?php

/**
 * Contoh 3 — Validasi QRIS dan penanganan error.
 *
 *   php examples/03-validate.php
 */

require __DIR__ . '/_bootstrap.php';

use QrisDinamis\Exception\InvalidQrisException;
use QrisDinamis\Qris;

$inputs = [
    'QRIS valid'      => SAMPLE_QRIS,
    'CRC salah'       => substr(SAMPLE_QRIS, 0, -4) . 'ABCD',
    'Bukan QRIS'      => 'https://example.com',
    'Kosong'          => '',
];

// Cara 1: validate() mengembalikan array, tidak melempar exception
foreach ($inputs as $label => $qris) {
    $result = Qris::validate($qris);
    echo str_pad($label, 12) . ': ' . ($result['valid'] ? 'VALID' : 'TIDAK VALID') . "\n";
    foreach ($result['errors'] as $error) {
        echo "              - {$error}\n";
    }
}

// Cara 2: isValid() untuk cek cepat
echo "\nisValid: " . var_export(Qris::isValid(SAMPLE_QRIS), true) . "\n";

// Cara 3: parse()/convert() melempar InvalidQrisException bila input salah
try {
    Qris::convert('000201xxx', 10000);
} catch (InvalidQrisException $e) {
    echo "\nException : {$e->getMessage()}\n";
    echo "Detail    :\n";
    foreach ($e->getErrors() as $error) {
        echo "  - {$error}\n";
    }
}

// Nominal / fee yang salah juga melempar InvalidQrisException
try {
    Qris::convert(SAMPLE_QRIS, 0);
} catch (InvalidQrisException $e) {
    echo "\nException : {$e->getMessage()}\n";
}
