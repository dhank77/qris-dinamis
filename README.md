<h1 align="center">QRIS Dinamis for PHP</h1>

<p align="center">
  Ubah QRIS <b>statis</b> menjadi QRIS <b>dinamis</b> (dengan nominal) langsung dari PHP.<br>
  Parse, validasi, tambah biaya layanan, dan hitung ulang CRC16 — tanpa dependensi.
</p>

<p align="center">
  <a href="https://packagist.org/packages/dhank77/qris-dinamis"><img src="https://img.shields.io/packagist/v/dhank77/qris-dinamis?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/dhank77/qris-dinamis"><img src="https://img.shields.io/packagist/php-v/dhank77/qris-dinamis?style=flat-square" alt="PHP Version"></a>
  <a href="https://github.com/dhank77/qris-dinamis/actions"><img src="https://img.shields.io/github/actions/workflow/status/dhank77/qris-dinamis/tests.yml?style=flat-square&label=tests" alt="Tests"></a>
  <a href="https://packagist.org/packages/dhank77/qris-dinamis"><img src="https://img.shields.io/packagist/dt/dhank77/qris-dinamis?style=flat-square" alt="Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/dhank77/qris-dinamis?style=flat-square" alt="License"></a>
</p>

<p align="center">
  <a href="#instalasi">Instalasi</a> •
  <a href="#mulai-cepat">Mulai Cepat</a> •
  <a href="#panduan-pemakaian">Panduan</a> •
  <a href="#referensi-api">Referensi API</a> •
  <a href="#contoh">Contoh</a> •
  <a href="#cara-kerja">Cara Kerja</a> •
  <a href="#faq">FAQ</a>
</p>

```php
use QrisDinamis\Qris;

$qris = Qris::convert($qrisStatisToko, 25000);   // → QRIS dinamis Rp 25.000
```

---

## Daftar Isi

- [Apa itu QRIS dinamis?](#apa-itu-qris-dinamis)
- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Mulai Cepat](#mulai-cepat)
- [Mendapatkan string QRIS statis](#mendapatkan-string-qris-statis)
- [Panduan Pemakaian](#panduan-pemakaian)
  - [1. Konversi ke dinamis](#1-konversi-ke-dinamis)
  - [2. Menambahkan biaya layanan](#2-menambahkan-biaya-layanan)
  - [3. Validasi](#3-validasi)
  - [4. Membaca isi QRIS](#4-membaca-isi-qris)
  - [5. Menangani error](#5-menangani-error)
  - [6. Membuat gambar QR](#6-membuat-gambar-qr)
  - [7. Command line (CLI)](#7-command-line-cli)
  - [8. Web demo](#8-web-demo)
- [Integrasi Framework](#integrasi-framework)
  - [Laravel](#laravel)
  - [CodeIgniter 4](#codeigniter-4)
  - [PHP native](#php-native)
- [Referensi API](#referensi-api)
- [Contoh](#contoh)
- [Cara Kerja](#cara-kerja)
- [FAQ](#faq)
- [Testing](#testing)
- [Kontribusi](#kontribusi)
- [Kredit & Lisensi](#kredit--lisensi)

---

## Apa itu QRIS dinamis?

**QRIS** (Quick Response Code Indonesian Standard) adalah standar QR pembayaran dari Bank Indonesia. Satu kode QR bisa dibayar lewat semua aplikasi bank dan e-wallet (GoPay, OVO, DANA, ShopeePay, m-banking, dll).

| Jenis | Keterangan |
|---|---|
| **Statis** | QR tanpa nominal. Pembeli mengetik sendiri jumlah yang dibayar. Biasanya dicetak dan ditempel di kasir. |
| **Dinamis** | QR dengan nominal yang sudah terisi. Pembeli cukup scan lalu konfirmasi. |

Library ini mengambil QRIS **statis** milik toko Anda, menyisipkan **nominal** (dan opsional **biaya layanan**), lalu menghitung ulang checksum sehingga menjadi QRIS **dinamis** yang valid. Uang tetap masuk ke rekening/merchant yang sama.

## Fitur

- ✅ **Konversi statis → dinamis** dengan nominal tertentu
- ✅ **Biaya layanan** tetap (Rupiah) atau persentase
- ✅ **Validasi** struktur TLV, tag wajib, dan checksum CRC16
- ✅ **Parser** — nama merchant, kota, kode pos, NMID, issuer, MCC, mata uang, dll
- ✅ **Konversi ulang** QRIS yang sudah dinamis (nominal lama diganti)
- ✅ **Exception yang jelas** dengan daftar error rinci
- ✅ **CLI** untuk konversi cepat dari terminal
- ✅ **Web demo** lengkap: upload gambar, scan kamera, paste screenshot, unduh QR
- ✅ **Nol dependensi**, PHP 8.1+
- ✅ Output identik byte-per-byte dengan versi TypeScript asli

## Persyaratan

- PHP **8.1** atau lebih baru
- Tidak ada ekstensi khusus yang wajib
- Opsional: [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) jika ingin membuat gambar QR di server

## Instalasi

```bash
composer require dhank77/qris-dinamis
```

Untuk membuat gambar QR di server (opsional):

```bash
composer require chillerlan/php-qrcode
```

<details>
<summary>Tanpa Composer</summary>

Unduh repositori ini, lalu:

```php
require 'path/to/qris-dinamis/autoload.php';
```
</details>

## Mulai Cepat

```php
<?php

require 'vendor/autoload.php';

use QrisDinamis\Qris;

// QRIS statis toko Anda (lihat bagian "Mendapatkan string QRIS statis")
$static = '00020101021126570011ID.DANA.WWW0118936009153022591481...6304ABCD';

// Buat QRIS dinamis Rp 25.000
$dynamic = Qris::convert($static, 25000);

echo $dynamic;
// 00020101021226570011ID.DANA.WWW...5405250005802ID...63040DD1
```

String `$dynamic` tinggal diubah menjadi gambar QR (lihat [Membuat gambar QR](#6-membuat-gambar-qr)) dan ditampilkan ke pembeli.

## Mendapatkan string QRIS statis

Library ini bekerja dengan **string** QRIS, bukan gambar. Cara mendapatkan string dari QRIS statis toko Anda:

1. Unduh / foto gambar QRIS statis dari aplikasi merchant (DANA Bisnis, GoBiz, OVO Merchant, QRIS bank, dll).
2. Baca isinya dengan salah satu cara:
   - Jalankan [web demo](#8-web-demo), klik **Upload Image**, lalu salin string yang muncul.
   - Pakai aplikasi pemindai QR apa pun di HP, lalu salin teks hasil scan.
3. String yang benar selalu diawali `000201` dan diakhiri `6304` + 4 karakter checksum.
4. Simpan di konfigurasi (mis. `.env`), **bukan** diterima dari input pengguna.

```dotenv
QRIS_STATIC="00020101021126570011ID.DANA.WWW...6304ABCD"
```

Cek apakah string sudah benar:

```bash
vendor/bin/qris validate "00020101021126570011ID.DANA.WWW...6304ABCD"
# [✓] QRIS valid
```

---

## Panduan Pemakaian

Semua fungsi utama tersedia sebagai method statis di class `QrisDinamis\Qris`.

### 1. Konversi ke dinamis

```php
use QrisDinamis\Qris;

$dynamic = Qris::convert($static, 150000);
```

- `$amount` adalah nominal dalam **Rupiah**, harus lebih dari 0.
- Input boleh mengandung spasi / baris baru di awal dan akhir — otomatis di-*trim*.
- Input yang sudah dinamis juga boleh: nominal dan biaya lama akan diganti.

```php
$a = Qris::convert($static, 10000);
$b = Qris::convert($a, 20000);   // sekarang Rp 20.000
```

### 2. Menambahkan biaya layanan

Biaya layanan (*convenience fee*) ditampilkan terpisah oleh aplikasi pembayaran dan ditambahkan ke total yang dibayar pembeli.

```php
// Rp 50.000 + biaya tetap Rp 1.500
$qris = Qris::convert($static, 50000, Qris::FEE_FIXED, 1500);

// Rp 50.000 + biaya 0,7%
$qris = Qris::convert($static, 50000, Qris::FEE_PERCENTAGE, 0.7);
```

| Konstanta | Nilai | Arti `$feeValue` | Tag yang ditulis |
|---|---|---|---|
| `Qris::FEE_FIXED` | `'fixed'` | Rupiah | `55` = `02`, `56` = nilai |
| `Qris::FEE_PERCENTAGE` | `'percentage'` | Persen (boleh desimal) | `55` = `03`, `57` = nilai |

Jika `$feeValue` bernilai `0` atau `null`, biaya diabaikan.

> **Catatan:** dukungan tampilan biaya layanan bergantung pada aplikasi pembayaran pembeli. Uji dulu dengan beberapa aplikasi sebelum dipakai di produksi.

### 3. Validasi

```php
$result = Qris::validate($input);
// ['valid' => false, 'errors' => ['CRC mismatch: expected 2982, got ABCD']]

if (Qris::isValid($input)) {
    // ...
}
```

Yang diperiksa:

| Pemeriksaan | Contoh pesan error |
|---|---|
| String kosong | `QRIS string is empty` |
| Diawali `000201` | `QRIS must start with Payload Format Indicator "000201"` |
| Panjang minimum | `QRIS string is too short` |
| Checksum CRC16 | `CRC mismatch: expected 2982, got ABCD` |
| Tag wajib `00 01 52 53 58 59 60 63` | `Missing required tag 59 (Merchant Name)` |
| Nilai tag `01` harus `11`/`12` | `Invalid Point of Initiation Method: "13" ...` |
| Ada Merchant Account Info (tag 26–51) | `No Merchant Account Information found (tags 26-51)` |

### 4. Membaca isi QRIS

```php
$data = Qris::parse($static);

$data->merchantName;          // "Warung Sayur"
$data->merchantCity;          // "Kab. Demak"
$data->postalCode;            // "59567"
$data->merchantCategoryCode;  // "5812"
$data->currency;              // "360"
$data->currencyLabel();       // "IDR"
$data->countryCode;           // "ID"
$data->method;                // "static" | "dynamic"
$data->isStatic();            // true
$data->isDynamic();           // false
$data->issuer();              // "ID.DANA.WWW"
$data->nmid();                // "ID1020017611473"
$data->amount;                // null (statis) atau "25000" (dinamis)
$data->amountValue();         // null atau 25000.0
$data->tipIndicator;          // null | "prompt" | "fixed" | "percentage"
$data->tipFixed;              // "1500" atau null
$data->tipPercentage;         // "0.7" atau null
$data->crc;                   // "2982"
```

Data per penyelenggara (tag 26–51):

```php
foreach ($data->merchantAccountInfo as $info) {
    echo $info['tag'];                // "26"
    echo $info['globallyUniqueId'];   // "ID.DANA.WWW"
    echo $info['merchantId'];         // "936009153022591481"
    echo $info['merchantCriteria'];   // "UMI"
    // $info['fields'] berisi array Tlv lengkap
}
```

Struktur TLV mentah dan ekspor:

```php
foreach ($data->raw as $tlv) {
    echo "{$tlv->tag} {$tlv->name} {$tlv->value}\n";
    // $tlv->children berisi sub-elemen untuk tag 26–51 dan 62
}

$array = $data->toArray();       // array asosiatif
$json  = json_encode($data);     // JSON (termasuk "issuer" dan "nmid")
```

### 5. Menangani error

`Qris::parse()` dan `Qris::convert()` melempar `QrisDinamis\Exception\InvalidQrisException` jika QRIS atau parameter tidak valid. Class ini turunan `\InvalidArgumentException`.

```php
use QrisDinamis\Exception\InvalidQrisException;
use QrisDinamis\Qris;

try {
    $qris = Qris::convert($static, $amount, $feeType, $feeValue);
} catch (InvalidQrisException $e) {
    $e->getMessage();   // "Invalid QRIS: CRC mismatch: expected 2982, got ABCD"
    $e->getErrors();    // ['CRC mismatch: expected 2982, got ABCD']
}
```

Exception juga dilempar jika:

- `$amount` ≤ 0 → `Amount must be greater than 0`
- `$feeValue` negatif → `Fee value must not be negative`
- `$feeType` bukan `fixed` / `percentage` → `Invalid fee type: ...`

### 6. Membuat gambar QR

Library ini hanya menghasilkan **string**. Untuk gambar, pakai library QR mana pun. Contoh dengan `chillerlan/php-qrcode`:

```php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use QrisDinamis\Qris;

$qris = Qris::convert($static, 25000);

// a) Data URI SVG — langsung untuk <img src="...">
$src = (new QRCode())->render($qris);
echo '<img src="' . $src . '" width="280" alt="QRIS">';

// b) File PNG
$png = new QRCode(new QROptions([
    'outputType'    => QROutputInterface::GDIMAGE_PNG,
    'eccLevel'      => EccLevel::M,
    'scale'         => 10,
    'quietzoneSize' => 2,
    'outputBase64'  => false,
]));
file_put_contents('qris.png', $png->render($qris));

// c) Kirim PNG langsung sebagai response
header('Content-Type: image/png');
echo $png->render($qris);
```

Bisa juga dirender di browser dengan library JavaScript (lihat `examples/web`).

### 7. Command line (CLI)

Setelah `composer require`, CLI tersedia di `vendor/bin/qris`.

**Mode interaktif**

```
$ vendor/bin/qris

╔══════════════════════════════════════════════╗
║   QRIS Static → Dynamic Converter v2.0       ║
╚══════════════════════════════════════════════╝

[?] Input QRIS string: 00020101021126570011ID.DANA.WWW...
[✓] QRIS Parsed:
    Merchant : Warung Sayur
    City     : Kab. Demak
    Method   : static
    Currency : IDR

[?] Input nominal (Rupiah): 25000
[?] Add service fee? (y/n): n

00020101021226570011ID.DANA.WWW...63040DD1
```

**Mode langsung** (cocok untuk script)

```bash
vendor/bin/qris convert "<QRIS>" 25000
vendor/bin/qris convert "<QRIS>" 25000 --fee-fixed=1000
vendor/bin/qris convert "<QRIS>" 25000 --fee-percent=2.5
vendor/bin/qris parse "<QRIS>"        # output JSON
vendor/bin/qris validate "<QRIS>"     # exit code 0 = valid, 1 = tidak valid
```

### 8. Web demo

Repositori ini menyertakan aplikasi web lengkap di `examples/web`:

- tempel string QRIS, upload / drag & drop gambar, scan kamera, paste screenshot (Ctrl+V)
- tampilkan informasi QRIS
- konversi dengan nominal + biaya layanan
- tampilkan & unduh QR hasil, salin string
- mode gelap / terang
- JSON API di `examples/web/api.php`

```bash
git clone https://github.com/dhank77/qris-dinamis
cd qris-dinamis
composer serve        # = php -S localhost:8000 -t examples/web
```

Buka http://localhost:8000. Scan kamera memerlukan HTTPS atau `localhost`.

JSON API:

```bash
curl -X POST http://localhost:8000/api.php \
  -H 'Content-Type: application/json' \
  -d '{"action":"convert","qris":"000201...","amount":25000,"fee_type":"fixed","fee_value":1000}'
```

| `action` | Parameter | Respons sukses |
|---|---|---|
| `validate` | `qris` | `{ "ok": true, "valid": bool, "errors": [] }` |
| `parse` | `qris` | `{ "ok": true, "data": {...} }` |
| `convert` | `qris`, `amount`, opsional `fee_type`, `fee_value` | `{ "ok": true, "result": "000201...", "data": {...} }` |

Error dikembalikan dengan status `422` dan `{ "ok": false, "errors": [...] }`.

---

## Integrasi Framework

### Laravel

**.env**

```dotenv
QRIS_STATIC="00020101021126570011ID.DANA.WWW...6304ABCD"
```

**config/services.php**

```php
'qris' => [
    'static' => env('QRIS_STATIC'),
],
```

**Controller**

```php
use chillerlan\QRCode\QRCode;
use QrisDinamis\Qris;

class PaymentController extends Controller
{
    public function show(Order $order)
    {
        $qris = Qris::convert(config('services.qris.static'), $order->total);

        return view('pay', [
            'order'   => $order,
            'qrImage' => (new QRCode())->render($qris),
        ]);
    }
}
```

**resources/views/pay.blade.php**

```blade
<img src="{{ $qrImage }}" alt="QRIS" width="260">
<p>Rp {{ number_format($order->total, 0, ',', '.') }}</p>
```

Contoh lengkap dengan endpoint API dan validasi request ada di [`examples/laravel`](examples/laravel).

### CodeIgniter 4

```php
namespace App\Controllers;

use chillerlan\QRCode\QRCode;
use QrisDinamis\Exception\InvalidQrisException;
use QrisDinamis\Qris;

class Payment extends BaseController
{
    public function qris()
    {
        $amount = (int) $this->request->getPost('amount');

        try {
            $qris = Qris::convert(env('QRIS_STATIC'), $amount);
        } catch (InvalidQrisException $e) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $e->getErrors()]);
        }

        return $this->response->setJSON([
            'qris'  => $qris,
            'image' => (new QRCode())->render($qris),
        ]);
    }
}
```

### PHP native

```php
<?php
require 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use QrisDinamis\Qris;

$total = 75000;
$qris  = Qris::convert(getenv('QRIS_STATIC'), $total);
?>
<img src="<?= (new QRCode())->render($qris) ?>" width="260" alt="QRIS">
<p>Total: Rp <?= number_format($total, 0, ',', '.') ?></p>
```

---

## Referensi API

### `QrisDinamis\Qris`

| Method | Return | Keterangan |
|---|---|---|
| `convert(string $qris, int\|float $amount, ?string $feeType = null, int\|float\|null $feeValue = null, bool $keepStatic = false)` | `string` | Validasi lalu konversi. Melempar `InvalidQrisException`. Set `keepStatic: true` jika ingin mempertahankan Tag 01 tetap '11' (berguna untuk e-wallet seperti ShopeePay agar tidak error kedaluwarsa). |
| `parse(string $qris)` | `QrisData` | Validasi lalu parse. Melempar `InvalidQrisException`. |
| `validate(string $qris)` | `array{valid: bool, errors: string[]}` | Validasi tanpa exception. |
| `isValid(string $qris)` | `bool` | Singkatan dari `validate()['valid']`. |
| `assertValid(string $qris)` | `void` | Melempar `InvalidQrisException` jika tidak valid. |
| `parseTlv(string $data)` | `Tlv[]` | Parse TLV mentah **tanpa** validasi (untuk debugging). |
| `crc16(string $str)` | `string` | CRC16-CCITT, 4 digit hex huruf besar. |

| Konstanta | Nilai |
|---|---|
| `Qris::FEE_FIXED` | `'fixed'` |
| `Qris::FEE_PERCENTAGE` | `'percentage'` |

### `QrisDinamis\QrisData`

Objek hasil `Qris::parse()`. Semua properti `readonly`.

| Properti | Tipe | Tag | Keterangan |
|---|---|---|---|
| `version` | `string` | 00 | Payload Format Indicator (`"01"`) |
| `method` | `string` | 01 | `"static"` atau `"dynamic"` |
| `merchantAccountInfo` | `array` | 26–51 | Lihat [Membaca isi QRIS](#4-membaca-isi-qris) |
| `merchantCategoryCode` | `string` | 52 | MCC, mis. `"5812"` |
| `currency` | `string` | 53 | Kode ISO 4217, `"360"` = IDR |
| `amount` | `?string` | 54 | Nominal, `null` jika statis |
| `tipIndicator` | `?string` | 55 | `"prompt"`, `"fixed"`, `"percentage"`, atau `null` |
| `tipFixed` | `?string` | 56 | Biaya tetap |
| `tipPercentage` | `?string` | 57 | Biaya persen |
| `countryCode` | `string` | 58 | `"ID"` |
| `merchantName` | `string` | 59 | Nama merchant |
| `merchantCity` | `string` | 60 | Kota |
| `postalCode` | `string` | 61 | Kode pos |
| `additionalData` | `?Tlv[]` | 62 | Data tambahan |
| `crc` | `string` | 63 | Checksum |
| `raw` | `Tlv[]` | — | Semua elemen TLV |

| Method | Return |
|---|---|
| `isStatic()` / `isDynamic()` | `bool` |
| `issuer()` | `?string` — penyelenggara utama, mis. `"ID.DANA.WWW"` |
| `nmid()` | `?string` — National Merchant ID |
| `amountValue()` | `?float` |
| `currencyLabel()` | `string` — `"IDR"` untuk `360` |
| `toArray()` | `array` |

`QrisData` mengimplementasikan `JsonSerializable`.

### `QrisDinamis\Tlv`

| Properti | Tipe |
|---|---|
| `tag` | `string` — 2 digit |
| `name` | `string` — nama tag yang mudah dibaca |
| `length` | `int` |
| `value` | `string` |
| `children` | `?Tlv[]` — sub-elemen untuk tag 26–51 dan 62 |

### `QrisDinamis\Exception\InvalidQrisException`

Turunan `\InvalidArgumentException`.

| Method | Return |
|---|---|
| `getMessage()` | `string` — ringkasan |
| `getErrors()` | `string[]` — daftar error rinci |

### Class tingkat rendah

Tersedia jika butuh kontrol lebih; tidak melakukan validasi otomatis.

| Class | Method |
|---|---|
| `QrisDinamis\Converter` | `convert()`, `buildTlvString(Tlv[])`, `formatNumber()` |
| `QrisDinamis\Parser` | `parse()`, `parseTlv()`, `tagName()`, `isMerchantTag()` |
| `QrisDinamis\Validator` | `validate()` |
| `QrisDinamis\Crc16` | `calculate()` |

---

## Contoh

Folder [`examples/`](examples) berisi contoh yang bisa langsung dijalankan dari root repositori:

| File | Isi |
|---|---|
| [`01-basic-convert.php`](examples/01-basic-convert.php) | Konversi paling sederhana |
| [`02-service-fee.php`](examples/02-service-fee.php) | Biaya layanan tetap & persen |
| [`03-validate.php`](examples/03-validate.php) | Validasi dan penanganan error |
| [`04-parse.php`](examples/04-parse.php) | Membaca semua informasi QRIS |
| [`05-generate-qr-image.php`](examples/05-generate-qr-image.php) | Membuat PNG / SVG di server |
| [`06-api-endpoint.php`](examples/06-api-endpoint.php) | Endpoint JSON minimal |
| [`07-checkout-page.php`](examples/07-checkout-page.php) | Halaman checkout dengan kode unik |
| [`laravel/`](examples/laravel) | Controller + view Laravel |
| [`web/`](examples/web) | Aplikasi web lengkap + JSON API |

```bash
php examples/01-basic-convert.php
php -S localhost:8000 examples/07-checkout-page.php
```

Folder `examples/` dan `tests/` tidak ikut terpasang saat `composer require`, sehingga paket di `vendor/` tetap kecil.

---

## Cara Kerja

QRIS mengikuti format **EMVCo Merchant-Presented QR**, berupa rangkaian elemen **TLV** (Tag–Length–Value):

```
00 02 01
│  │  └─ value  : "01"
│  └──── length : 2 karakter
└─────── tag    : 00 (Payload Format Indicator)
```

Tag penting:

| Tag | Nama | Contoh |
|---|---|---|
| `00` | Payload Format Indicator | `01` |
| `01` | Point of Initiation Method | `11` statis, `12` dinamis |
| `26`–`51` | Merchant Account Information | issuer, merchant ID, NMID |
| `52` | Merchant Category Code | `5812` |
| `53` | Transaction Currency | `360` (IDR) |
| `54` | Transaction Amount | `25000` |
| `55` | Tip / Convenience Indicator | `02` tetap, `03` persen |
| `56` | Convenience Fee (tetap) | `1000` |
| `57` | Convenience Fee (persen) | `2.5` |
| `58` | Country Code | `ID` |
| `59` | Merchant Name | `Warung Sayur` |
| `60` | Merchant City | `Kab. Demak` |
| `61` | Postal Code | `59567` |
| `62` | Additional Data | |
| `63` | CRC | 4 digit hex |

Langkah konversi:

1. Parse string menjadi daftar elemen TLV.
2. Ubah tag `01` dari `11` (statis) menjadi `12` (dinamis).
3. Buang tag `54`–`57` dan `63` yang lama (jika ada).
4. Sisipkan tag `54` (nominal) — serta `55` + `56`/`57` jika ada biaya — tepat sebelum tag `58`.
5. Susun ulang string, tambahkan `6304`, lalu hitung **CRC16-CCITT** (polinomial `0x1021`, nilai awal `0xFFFF`) dan tempelkan 4 digit hex hasilnya.

Contoh perubahan (disingkat):

```diff
- 000201 010211 2657...UMI 5144...UMI 52045812 5303360                     5802ID 5912Warung Sayur ... 63042982
+ 000201 010212 2657...UMI 5144...UMI 52045812 5303360 540525000           5802ID 5912Warung Sayur ... 63040DD1
+ 000201 010212 2657...UMI 5144...UMI 52045812 5303360 540525000 550202 56041000 5802ID ...            6304741A
```

---

## FAQ

**Apakah ini payment gateway?**
Bukan. Library ini hanya membuat kode QR. Uang langsung masuk ke merchant pemilik QRIS statis. Library ini **tidak** tahu apakah pembayaran sudah dilakukan — tidak ada callback/webhook.

**Lalu bagaimana memastikan pembeli sudah membayar?**
Cek mutasi / riwayat transaksi di aplikasi merchant atau bank. Agar mudah dicocokkan, tambahkan **kode unik** kecil ke nominal (mis. Rp 150.000 → Rp 150.127) dan simpan bersama pesanan. Lihat [`examples/07-checkout-page.php`](examples/07-checkout-page.php). Untuk konfirmasi otomatis, gunakan payment gateway resmi.

**Apakah QRIS dinamis hasil konversi aman dipakai?**
Formatnya mengikuti standar EMVCo dan checksum dihitung ulang dengan benar, sehingga bisa dibaca aplikasi pembayaran. Namun QRIS dinamis "resmi" biasanya diterbitkan per transaksi oleh penyelenggara. Uji dengan beberapa aplikasi sebelum dipakai di produksi dan patuhi ketentuan penyelenggara QRIS Anda.

**Kenapa muncul "CRC mismatch"?**
String rusak atau terpotong saat disalin (mis. spasi di tengah, karakter hilang). Salin ulang dari hasil scan asli.

**Bisa dipakai untuk QRIS dari penyelenggara mana saja?**
Ya, selama QRIS mengikuti standar (DANA, GoPay, OVO, ShopeePay, LinkAja, QRIS bank, dll).

**Apakah nominal boleh desimal?**
Rupiah tidak memakai desimal, jadi gunakan bilangan bulat untuk `$amount`. Biaya persentase boleh desimal (mis. `0.7`).

**Keamanan?**
Simpan QRIS statis di server (konfigurasi / `.env`). Jangan menerima string QRIS dari klien untuk dikonversi, karena pengguna bisa menggantinya dengan QRIS milik orang lain.

---

## Testing

```bash
composer install
composer test          # = vendor/bin/phpunit
```

Nilai yang diharapkan di tes diambil dari output versi TypeScript asli, sehingga hasil library ini dijamin identik.

## Kontribusi

Pull request dan laporan bug sangat diterima.

1. Fork repositori
2. Buat branch: `git checkout -b fitur-baru`
3. Pastikan `composer test` lulus
4. Kirim pull request

Riwayat perubahan ada di [CHANGELOG.md](CHANGELOG.md).

## Kredit & Lisensi

- Port PHP dari [verssache/qris-dinamis](https://github.com/verssache/qris-dinamis) karya **Gidhan** (TypeScript).
- Web demo memakai [jsQR](https://github.com/cozmo/jsQR) dan [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) (MIT).

Dirilis di bawah lisensi **MIT**. Lihat [LICENSE](LICENSE).

QRIS adalah standar kode QR pembayaran milik Bank Indonesia. Proyek ini tidak berafiliasi dengan Bank Indonesia maupun penyelenggara jasa pembayaran mana pun.
