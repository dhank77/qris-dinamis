<?php

/**
 * Contoh 5 — Membuat gambar QR (PNG / SVG) di server.
 *
 * Library ini hanya menghasilkan STRING QRIS. Untuk mengubahnya menjadi gambar,
 * pasang salah satu library QR, misalnya:
 *
 *   composer require chillerlan/php-qrcode
 *
 *   php examples/05-generate-qr-image.php
 */

require __DIR__ . '/_bootstrap.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use QrisDinamis\Qris;

if (!class_exists(QRCode::class)) {
    exit("Jalankan dulu: composer require chillerlan/php-qrcode\n");
}

$dynamic = Qris::convert(SAMPLE_QRIS, 25000);

// --- PNG ke file ---------------------------------------------------------
$png = new QRCode(new QROptions([
    'outputType'   => QROutputInterface::GDIMAGE_PNG,
    'eccLevel'     => \chillerlan\QRCode\Common\EccLevel::M,
    'scale'        => 10,          // piksel per modul
    'quietzoneSize' => 2,
    'outputBase64' => false,       // dapatkan data biner, bukan data URI
]));
file_put_contents(__DIR__ . '/qris-25000.png', $png->render($dynamic));
echo "PNG tersimpan: examples/qris-25000.png\n";

// --- SVG sebagai data URI, siap dipakai di <img src="..."> ----------------
$svg = new QRCode(new QROptions([
    'outputType' => QROutputInterface::MARKUP_SVG,
    'eccLevel'   => \chillerlan\QRCode\Common\EccLevel::M,
]));
$dataUri = $svg->render($dynamic);
echo "Data URI SVG (untuk <img src>): " . substr($dataUri, 0, 60) . "...\n";

// Di halaman HTML:
// <img src="{$dataUri}" alt="QRIS Rp 25.000" width="280">
