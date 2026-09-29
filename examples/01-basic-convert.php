<?php

/**
 * Contoh 1 — Konversi QRIS statis menjadi dinamis dengan nominal tertentu.
 *
 *   php examples/01-basic-convert.php
 */

require __DIR__ . '/_bootstrap.php';

use QrisDinamis\Qris;

$static = SAMPLE_QRIS;

$dynamic = Qris::convert($static, 25000);

echo "QRIS statis :\n{$static}\n\n";
echo "QRIS dinamis (Rp 25.000):\n{$dynamic}\n";
