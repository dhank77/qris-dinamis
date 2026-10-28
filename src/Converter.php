<?php

declare(strict_types=1);

namespace QrisDinamis;

use QrisDinamis\Exception\InvalidQrisException;

/**
 * Konversi QRIS statis menjadi dinamis.
 *
 * Langkah:
 * 1. Parse struktur TLV
 * 2. Ubah Point of Initiation Method dari "11" (statis) ke "12" (dinamis)
 * 3. Sisipkan/ganti Transaction Amount (tag 54)
 * 4. Opsional: sisipkan Tip Indicator (tag 55) dan nilai fee (tag 56/57)
 * 5. Hitung ulang checksum CRC16
 */
final class Converter
{
    public const FEE_FIXED = 'fixed';
    public const FEE_PERCENTAGE = 'percentage';

    /** Tag yang dikelola ulang oleh converter */
    private const MANAGED_TAGS = ['54', '55', '56', '57', '63'];

    /**
     * @param int|float        $amount     Nominal transaksi (Rupiah)
     * @param string|null      $feeType    'fixed' | 'percentage' | null
     * @param int|float|null   $feeValue   Nilai fee (Rupiah atau persen)
     * @param bool             $keepStatic Jika true, Tag 01 tetap statis ('11') dan tidak diubah ke '12'
     *
     * Catatan: method ini tidak memvalidasi QRIS. Gunakan Qris::convert()
     * jika ingin validasi otomatis.
     *
     * @throws InvalidQrisException jika nominal / fee tidak valid
     */
    public static function convert(
        string $qris,
        int|float $amount,
        ?string $feeType = null,
        int|float|null $feeValue = null,
        bool $keepStatic = false,
    ): string {
        if ($amount <= 0) {
            throw new InvalidQrisException('Amount must be greater than 0');
        }
        if ($feeValue !== null && $feeValue < 0) {
            throw new InvalidQrisException('Fee value must not be negative');
        }
        if ($feeType !== null && !in_array($feeType, [self::FEE_FIXED, self::FEE_PERCENTAGE], true)) {
            throw new InvalidQrisException("Invalid fee type: {$feeType} (use 'fixed' or 'percentage')");
        }

        $elements = Parser::parseTlv(trim($qris));
        $result = [];
        $amountInserted = false;

        foreach ($elements as $el) {
            if (in_array($el->tag, self::MANAGED_TAGS, true)) {
                continue;
            }

            if ($el->tag === '01') {
                if ($keepStatic) {
                    $result[] = $el;
                } else {
                    // statis → dinamis
                    $result[] = Tlv::make('01', '12', 'Point of Initiation Method');
                }
                continue;
            }

            // Sisipkan nominal + fee sebelum tag 58 (Country Code)
            if ($el->tag === '58' && !$amountInserted) {
                $result[] = Tlv::make('54', self::formatNumber($amount), 'Transaction Amount');

                if ($feeType !== null && $feeValue !== null && $feeValue > 0) {
                    if ($feeType === self::FEE_FIXED) {
                        $result[] = Tlv::make('55', '02', 'Tip or Convenience Indicator');
                        $result[] = Tlv::make('56', self::formatNumber($feeValue), 'Value of Convenience Fee (Fixed)');
                    } else {
                        $result[] = Tlv::make('55', '03', 'Tip or Convenience Indicator');
                        $result[] = Tlv::make('57', self::formatNumber($feeValue), 'Value of Convenience Fee (%)');
                    }
                }

                $amountInserted = true;
            }

            $result[] = $el;
        }

        $crcInput = self::buildTlvString($result) . '6304';

        return $crcInput . Crc16::calculate($crcInput);
    }

    /**
     * Susun ulang string dari elemen TLV (tanpa CRC).
     *
     * @param Tlv[] $elements
     */
    public static function buildTlvString(array $elements): string
    {
        $out = '';
        foreach ($elements as $el) {
            $value = $el->children !== null ? self::buildTlvString($el->children) : $el->value;
            $out .= $el->tag . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
        }
        return $out;
    }

    /**
     * Format angka seperti Number.toString() di JavaScript
     * (25000 → "25000", 2.5 → "2.5").
     */
    public static function formatNumber(int|float $n): string
    {
        if (is_int($n) || floor($n) == $n) {
            return (string) (int) $n;
        }
        return rtrim(rtrim(sprintf('%.10F', $n), '0'), '.');
    }
}
