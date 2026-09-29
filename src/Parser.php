<?php

declare(strict_types=1);

namespace QrisDinamis;

/**
 * Parser string QRIS (format TLV EMVCo).
 */
final class Parser
{
    /** Peta tag EMVCo / QRIS ke nama yang mudah dibaca */
    public const TAG_NAMES = [
        '00' => 'Payload Format Indicator',
        '01' => 'Point of Initiation Method',
        '02' => 'Visa',
        '03' => 'Mastercard',
        '04' => 'Mastercard',
        '15' => 'Visa',
        '52' => 'Merchant Category Code',
        '53' => 'Transaction Currency',
        '54' => 'Transaction Amount',
        '55' => 'Tip or Convenience Indicator',
        '56' => 'Value of Convenience Fee (Fixed)',
        '57' => 'Value of Convenience Fee (%)',
        '58' => 'Country Code',
        '59' => 'Merchant Name',
        '60' => 'Merchant City',
        '61' => 'Postal Code',
        '62' => 'Additional Data Field',
        '63' => 'CRC',
    ];

    public static function tagName(string $tag): string
    {
        if (isset(self::TAG_NAMES[$tag])) {
            return self::TAG_NAMES[$tag];
        }
        if (self::isMerchantTag($tag)) {
            return 'Merchant Account Information';
        }
        return "Unknown ({$tag})";
    }

    /** Tag 26–51 = Merchant Account Information */
    public static function isMerchantTag(string $tag): bool
    {
        if (!ctype_digit($tag)) {
            return false;
        }
        $n = (int) $tag;
        return $n >= 26 && $n <= 51;
    }

    /** Tag yang berisi sub-elemen TLV bersarang */
    private static function isNested(string $tag): bool
    {
        return self::isMerchantTag($tag) || $tag === '62';
    }

    /**
     * Parse string TLV mentah menjadi array elemen Tlv.
     *
     * @return Tlv[]
     */
    public static function parseTlv(string $data): array
    {
        $elements = [];
        $pos = 0;
        $total = strlen($data);

        while ($pos < $total) {
            if ($pos + 4 > $total) {
                break;
            }

            $tag = substr($data, $pos, 2);
            $lenStr = substr($data, $pos + 2, 2);

            if (!ctype_digit($lenStr)) {
                break;
            }
            $length = (int) $lenStr;

            if ($pos + 4 + $length > $total) {
                break;
            }

            $value = substr($data, $pos + 4, $length);
            $children = self::isNested($tag) ? self::parseTlv($value) : null;

            $elements[] = new Tlv($tag, self::tagName($tag), $length, $value, $children);
            $pos += 4 + $length;
        }

        return $elements;
    }

    /**
     * Parse string QRIS menjadi objek QrisData yang terstruktur.
     */
    public static function parse(string $qris): QrisData
    {
        $raw = self::parseTlv(trim($qris));

        $find = static function (array $list, string $tag): ?Tlv {
            foreach ($list as $el) {
                if ($el->tag === $tag) {
                    return $el;
                }
            }
            return null;
        };

        $method = $find($raw, '01')?->value === '12' ? 'dynamic' : 'static';

        $tipIndicator = match ($find($raw, '55')?->value) {
            '01' => 'prompt',
            '02' => 'fixed',
            '03' => 'percentage',
            default => null,
        };

        $merchantAccountInfo = [];
        foreach ($raw as $el) {
            if (!self::isMerchantTag($el->tag) || !$el->children) {
                continue;
            }
            $c = $el->children;
            $merchantAccountInfo[] = [
                'tag' => $el->tag,
                'globallyUniqueId' => $find($c, '00')?->value ?? '',
                'merchantId' => $find($c, '01')?->value ?? $find($c, '02')?->value,
                'merchantCriteria' => $find($c, '03')?->value,
                'fields' => $c,
            ];
        }

        return new QrisData(
            version: $find($raw, '00')?->value ?? '01',
            method: $method,
            merchantAccountInfo: $merchantAccountInfo,
            merchantCategoryCode: $find($raw, '52')?->value ?? '',
            currency: $find($raw, '53')?->value ?? '360',
            amount: $find($raw, '54')?->value,
            tipIndicator: $tipIndicator,
            tipFixed: $find($raw, '56')?->value,
            tipPercentage: $find($raw, '57')?->value,
            countryCode: $find($raw, '58')?->value ?? 'ID',
            merchantName: $find($raw, '59')?->value ?? '',
            merchantCity: $find($raw, '60')?->value ?? '',
            postalCode: $find($raw, '61')?->value ?? '',
            additionalData: $find($raw, '62')?->children,
            crc: $find($raw, '63')?->value ?? '',
            raw: $raw,
        );
    }
}
