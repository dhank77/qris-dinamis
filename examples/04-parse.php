<?php

/**
 * Contoh 4 — Membaca isi QRIS (nama merchant, kota, NMID, issuer, dll).
 *
 *   php examples/04-parse.php
 */

require __DIR__ . '/_bootstrap.php';

use QrisDinamis\Qris;

$data = Qris::parse(SAMPLE_QRIS);

echo "Merchant      : {$data->merchantName}\n";
echo "Kota          : {$data->merchantCity}\n";
echo "Kode pos      : {$data->postalCode}\n";
echo "Issuer        : {$data->issuer()}\n";
echo "NMID          : {$data->nmid()}\n";
echo "MCC           : {$data->merchantCategoryCode}\n";
echo "Mata uang     : {$data->currencyLabel()}\n";
echo "Metode        : {$data->method}\n";
echo "Nominal       : " . ($data->amount ?? '-') . "\n";

echo "\nMerchant Account Information:\n";
foreach ($data->merchantAccountInfo as $info) {
    printf("  tag %s  %-16s  id=%s  kriteria=%s\n",
        $info['tag'], $info['globallyUniqueId'], $info['merchantId'] ?? '-', $info['merchantCriteria'] ?? '-');
}

echo "\nStruktur TLV mentah:\n";
foreach ($data->raw as $tlv) {
    printf("  %s  %-32s  %s\n", $tlv->tag, $tlv->name, $tlv->children ? '(nested)' : $tlv->value);
    foreach ($tlv->children ?? [] as $child) {
        printf("      %s  %s\n", $child->tag, $child->value);
    }
}

echo "\nSebagai JSON:\n";
echo json_encode($data->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
