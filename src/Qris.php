<?php

declare(strict_types=1);

namespace QrisDinamis;

use QrisDinamis\Exception\InvalidQrisException;

/**
 * Titik masuk utama library.
 *
 *   Qris::validate($qris);                          // ['valid' => bool, 'errors' => [...]]
 *   Qris::isValid($qris);                           // bool
 *   Qris::parse($qris);                             // QrisData
 *   Qris::convert($qris, 25000);                    // string QRIS dinamis
 *   Qris::convert($qris, 25000, Qris::FEE_FIXED, 1000);
 *   Qris::convert($qris, 25000, Qris::FEE_PERCENTAGE, 2.5);
 */
final class Qris
{
    public const FEE_FIXED = Converter::FEE_FIXED;
    public const FEE_PERCENTAGE = Converter::FEE_PERCENTAGE;

    /**
     * Validasi struktur, tag wajib, dan CRC16.
     *
     * @return array{valid: bool, errors: string[]}
     */
    public static function validate(string $qris): array
    {
        return Validator::validate($qris);
    }

    public static function isValid(string $qris): bool
    {
        return Validator::validate($qris)['valid'];
    }

    /**
     * Parse QRIS menjadi objek terstruktur.
     *
     * @throws InvalidQrisException jika QRIS tidak valid
     */
    public static function parse(string $qris): QrisData
    {
        self::assertValid($qris);
        return Parser::parse($qris);
    }

    /**
     * Ubah QRIS statis (atau dinamis) menjadi QRIS dinamis dengan nominal tertentu.
     *
     * @param int|float      $amount   Nominal transaksi dalam Rupiah, > 0
     * @param string|null    $feeType  Qris::FEE_FIXED | Qris::FEE_PERCENTAGE | null
     * @param int|float|null $feeValue Rupiah (fixed) atau persen (percentage)
     *
     * @throws InvalidQrisException jika QRIS atau parameter tidak valid
     */
    public static function convert(
        string $qris,
        int|float $amount,
        ?string $feeType = null,
        int|float|null $feeValue = null,
    ): string {
        self::assertValid($qris);
        return Converter::convert($qris, $amount, $feeType, $feeValue);
    }

    /**
     * Parse TLV mentah tanpa validasi (untuk debugging / QR non-standar).
     *
     * @return Tlv[]
     */
    public static function parseTlv(string $data): array
    {
        return Parser::parseTlv($data);
    }

    /** Hitung checksum CRC16-CCITT (4 digit hex, huruf besar). */
    public static function crc16(string $str): string
    {
        return Crc16::calculate($str);
    }

    /**
     * @throws InvalidQrisException
     */
    public static function assertValid(string $qris): void
    {
        $result = Validator::validate($qris);
        if (!$result['valid']) {
            throw InvalidQrisException::fromErrors($result['errors']);
        }
    }
}
