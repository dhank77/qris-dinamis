<?php

declare(strict_types=1);

namespace QrisDinamis\Exception;

/**
 * Dilempar saat string QRIS tidak valid atau parameter konversi salah.
 *
 * Turunan dari \InvalidArgumentException, jadi bisa ditangkap dengan salah satunya.
 */
class InvalidQrisException extends \InvalidArgumentException
{
    /** @var string[] */
    private array $errors;

    /**
     * @param string[] $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors ?: [$message];
    }

    /**
     * @param string[] $errors
     */
    public static function fromErrors(array $errors): self
    {
        return new self('Invalid QRIS: ' . implode('; ', $errors), $errors);
    }

    /**
     * Daftar pesan error rinci.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
