<?php

declare(strict_types=1);

namespace QrisDinamis;

/**
 * CRC16-CCITT (False) checksum untuk QRIS / EMVCo QR.
 * Polynomial: 0x1021, Init: 0xFFFF
 */
final class Crc16
{
    public static function calculate(string $str): string
    {
        $crc = 0xFFFF;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= ord($str[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return str_pad(strtoupper(dechex($crc & 0xFFFF)), 4, '0', STR_PAD_LEFT);
    }
}
