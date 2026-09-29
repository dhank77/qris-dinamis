<?php

/**
 * Bootstrap bersama untuk semua contoh.
 *
 * Di proyek Anda sendiri cukup:  require 'vendor/autoload.php';
 */

require is_file(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../autoload.php';

/**
 * Contoh QRIS statis (data fiktif, CRC valid).
 * Ganti dengan string QRIS statis milik toko Anda — cara mendapatkannya ada di README.
 */
const SAMPLE_QRIS = '00020101021126570011ID.DANA.WWW011893600915302259148102090225914810303UMI'
    . '51440014ID.CO.QRIS.WWW0215ID10200176114730303UMI5204581253033605802ID'
    . '5912Warung Sayur6010Kab. Demak61055956762070703A0163042982';
