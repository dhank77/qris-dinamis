<?php

/**
 * Contoh 7 — Halaman checkout sederhana: satu QR per pesanan.
 *
 * Trik umum: tambahkan "kode unik" 3 digit ke nominal (mis. Rp 150.000 → Rp 150.127)
 * agar setiap pembayaran mudah dicocokkan dengan pesanannya di mutasi rekening.
 *
 * Butuh: composer require chillerlan/php-qrcode
 *
 * Jalankan:
 *   php -S localhost:8000 examples/07-checkout-page.php
 */

require __DIR__ . '/_bootstrap.php';

use chillerlan\QRCode\QRCode;
use QrisDinamis\Qris;

// Pesanan (biasanya dari database)
$order = [
    'id'    => 'INV-2026-0042',
    'items' => [
        ['name' => 'Kopi Susu', 'qty' => 2, 'price' => 18000],
        ['name' => 'Roti Bakar', 'qty' => 1, 'price' => 22000],
    ],
];

$subtotal   = array_sum(array_map(fn ($i) => $i['qty'] * $i['price'], $order['items']));
$uniqueCode = random_int(101, 999);          // simpan bersama pesanan!
$total      = $subtotal + $uniqueCode;

$qrisString = Qris::convert(getenv('QRIS_STATIC') ?: SAMPLE_QRIS, $total);
$merchant   = Qris::parse($qrisString)->merchantName;
$qrImage    = class_exists(QRCode::class) ? (new QRCode())->render($qrisString) : null;

$rp = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bayar <?= htmlspecialchars($order['id']) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 380px; margin: 2rem auto; padding: 0 1rem; color: #111; }
        .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem; text-align: center; }
        table { width: 100%; font-size: .9rem; margin: 1rem 0; border-collapse: collapse; }
        td { padding: .25rem 0; } td:last-child { text-align: right; }
        .total { font-size: 1.6rem; font-weight: 700; }
        .muted { color: #6b7280; font-size: .85rem; }
        img { width: 260px; max-width: 100%; }
    </style>
</head>
<body>
<div class="card">
    <div class="muted"><?= htmlspecialchars($merchant) ?> · <?= htmlspecialchars($order['id']) ?></div>

    <?php if ($qrImage): ?>
        <img src="<?= $qrImage ?>" alt="QRIS">
    <?php else: ?>
        <p class="muted">Pasang chillerlan/php-qrcode untuk menampilkan gambar QR.</p>
    <?php endif; ?>

    <div class="total"><?= $rp($total) ?></div>
    <div class="muted">Transfer tepat sampai 3 digit terakhir</div>

    <table>
        <?php foreach ($order['items'] as $item): ?>
            <tr><td><?= $item['qty'] ?>× <?= htmlspecialchars($item['name']) ?></td><td><?= $rp($item['qty'] * $item['price']) ?></td></tr>
        <?php endforeach; ?>
        <tr><td>Kode unik</td><td><?= $rp($uniqueCode) ?></td></tr>
    </table>

    <div class="muted">Scan dengan aplikasi bank / e-wallet apa pun yang mendukung QRIS.</div>
</div>
</body>
</html>
