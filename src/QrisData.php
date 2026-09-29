<?php

declare(strict_types=1);

namespace QrisDinamis;

/**
 * Data QRIS hasil parse dalam bentuk yang mudah dibaca.
 */
final class QrisData implements \JsonSerializable
{
    /**
     * @param array<int, array{tag:string, globallyUniqueId:string, merchantId:?string, merchantCriteria:?string, fields:Tlv[]}> $merchantAccountInfo
     * @param Tlv[]|null $additionalData
     * @param Tlv[] $raw
     */
    public function __construct(
        public readonly string $version,
        public readonly string $method, // 'static' | 'dynamic'
        public readonly array $merchantAccountInfo,
        public readonly string $merchantCategoryCode,
        public readonly string $currency,
        public readonly ?string $amount,
        public readonly ?string $tipIndicator, // 'prompt' | 'fixed' | 'percentage' | null
        public readonly ?string $tipFixed,
        public readonly ?string $tipPercentage,
        public readonly string $countryCode,
        public readonly string $merchantName,
        public readonly string $merchantCity,
        public readonly string $postalCode,
        public readonly ?array $additionalData,
        public readonly string $crc,
        public readonly array $raw,
    ) {
    }

    public function isDynamic(): bool
    {
        return $this->method === 'dynamic';
    }

    public function isStatic(): bool
    {
        return $this->method === 'static';
    }

    /** Issuer utama, mis. "ID.DANA.WWW" (tag 26 pertama). */
    public function issuer(): ?string
    {
        foreach ($this->merchantAccountInfo as $info) {
            if ($info['tag'] !== '51' && $info['globallyUniqueId'] !== '') {
                return $info['globallyUniqueId'];
            }
        }
        return $this->merchantAccountInfo[0]['globallyUniqueId'] ?? null;
    }

    /** National Merchant ID (NMID) dari tag 51 "ID.CO.QRIS.WWW", mis. "ID1020017611473". */
    public function nmid(): ?string
    {
        foreach ($this->merchantAccountInfo as $info) {
            if ($info['globallyUniqueId'] === 'ID.CO.QRIS.WWW') {
                return $info['merchantId'];
            }
        }
        return null;
    }

    /** Nominal transaksi sebagai angka, atau null untuk QRIS statis. */
    public function amountValue(): ?float
    {
        return $this->amount !== null ? (float) $this->amount : null;
    }

    public function currencyLabel(): string
    {
        return $this->currency === '360' ? 'IDR' : $this->currency;
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this) + [
            'issuer' => $this->issuer(),
            'nmid' => $this->nmid(),
        ];
    }
}
