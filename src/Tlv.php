<?php

declare(strict_types=1);

namespace QrisDinamis;

/**
 * Satu elemen TLV (Tag-Length-Value) dari payload QRIS.
 */
final class Tlv implements \JsonSerializable
{
    /**
     * @param Tlv[]|null $children
     */
    public function __construct(
        public readonly string $tag,
        public readonly string $name,
        public readonly int $length,
        public readonly string $value,
        public readonly ?array $children = null,
    ) {
    }

    public static function make(string $tag, string $value, string $name = ''): self
    {
        return new self($tag, $name, strlen($value), $value);
    }

    public function jsonSerialize(): array
    {
        $data = [
            'tag' => $this->tag,
            'name' => $this->name,
            'length' => $this->length,
            'value' => $this->value,
        ];
        if ($this->children !== null) {
            $data['children'] = $this->children;
        }
        return $data;
    }
}
