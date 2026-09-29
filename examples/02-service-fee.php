<?php

/**
 * Contoh 2 — Menambahkan biaya layanan (convenience fee).
 *
 * - Qris::FEE_FIXED       → biaya tetap dalam Rupiah (tag 55 = "02", tag 56)
 * - Qris::FEE_PERCENTAGE  → biaya persentase        (tag 55 = "03", tag 57)
 *
 * Aplikasi pembayaran akan menampilkan nominal + biaya ke pembeli.
 *
 *   php examples/02-service-fee.php
 */

require __DIR__ . '/_bootstrap.php';

use QrisDinamis\Qris;

// Rp 50.000 + biaya tetap Rp 1.500
$fixed = Qris::convert(SAMPLE_QRIS, 50000, Qris::FEE_FIXED, 1500);

// Rp 50.000 + biaya 0,7%
$percent = Qris::convert(SAMPLE_QRIS, 50000, Qris::FEE_PERCENTAGE, 0.7);

foreach (['Fee tetap' => $fixed, 'Fee persen' => $percent] as $label => $qris) {
    $data = Qris::parse($qris);
    echo "== {$label} ==\n";
    echo "Nominal       : Rp " . number_format((float) $data->amount, 0, ',', '.') . "\n";
    echo "Jenis fee     : {$data->tipIndicator}\n";
    echo "Nilai fee     : " . ($data->tipFixed !== null ? "Rp {$data->tipFixed}" : "{$data->tipPercentage}%") . "\n";
    echo "QRIS          : {$qris}\n\n";
}
