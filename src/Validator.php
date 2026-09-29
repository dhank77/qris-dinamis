<?php

declare(strict_types=1);

namespace QrisDinamis;

/**
 * Validasi struktur dan checksum string QRIS.
 */
final class Validator
{
    private const REQUIRED_TAGS = [
        '00' => 'Payload Format Indicator',
        '01' => 'Point of Initiation Method',
        '52' => 'Merchant Category Code',
        '53' => 'Transaction Currency',
        '58' => 'Country Code',
        '59' => 'Merchant Name',
        '60' => 'Merchant City',
        '63' => 'CRC',
    ];

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public static function validate(string $qris): array
    {
        $str = trim($qris);
        $errors = [];

        if ($str === '') {
            return ['valid' => false, 'errors' => ['QRIS string is empty']];
        }

        if (!str_starts_with($str, '000201')) {
            $errors[] = 'QRIS must start with Payload Format Indicator "000201"';
        }

        if (strlen($str) < 20) {
            $errors[] = 'QRIS string is too short';
            return ['valid' => false, 'errors' => $errors];
        }

        // Validasi CRC
        $declared = strtoupper(substr($str, -4));
        $calculated = Crc16::calculate(substr($str, 0, -4));
        if ($declared !== $calculated) {
            $errors[] = "CRC mismatch: expected {$calculated}, got {$declared}";
        }

        $elements = Parser::parseTlv($str);
        if (count($elements) === 0) {
            $errors[] = 'Failed to parse any TLV elements';
            return ['valid' => false, 'errors' => $errors];
        }

        $tags = [];
        foreach ($elements as $el) {
            $tags[$el->tag] = $el;
        }

        foreach (self::REQUIRED_TAGS as $tag => $name) {
            if (!isset($tags[$tag])) {
                $errors[] = "Missing required tag {$tag} ({$name})";
            }
        }

        if (isset($tags['01']) && !in_array($tags['01']->value, ['11', '12'], true)) {
            $errors[] = "Invalid Point of Initiation Method: \"{$tags['01']->value}\" (must be \"11\" or \"12\")";
        }

        $hasMerchant = false;
        foreach ($elements as $el) {
            if (Parser::isMerchantTag($el->tag)) {
                $hasMerchant = true;
                break;
            }
        }
        if (!$hasMerchant) {
            $errors[] = 'No Merchant Account Information found (tags 26-51)';
        }

        return ['valid' => count($errors) === 0, 'errors' => $errors];
    }
}
