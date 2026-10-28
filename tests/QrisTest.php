<?php

declare(strict_types=1);

namespace QrisDinamis\Tests;

use PHPUnit\Framework\TestCase;
use QrisDinamis\Converter;
use QrisDinamis\Exception\InvalidQrisException;
use QrisDinamis\Qris;

/**
 * Nilai yang diharapkan diambil dari output versi TypeScript asli
 * (verssache/qris-dinamis), sehingga hasil PHP dijamin identik.
 */
final class QrisTest extends TestCase
{
    private const STATIC_QRIS = '00020101021126570011ID.DANA.WWW011893600915302259148102090225914810303UMI'
        . '51440014ID.CO.QRIS.WWW0215ID10200176114730303UMI5204581253033605802ID'
        . '5912Warung Sayur6010Kab. Demak61055956762070703A0163042982';

    private const PREFIX = '00020101021226570011ID.DANA.WWW011893600915302259148102090225914810303UMI'
        . '51440014ID.CO.QRIS.WWW0215ID10200176114730303UMI5204581253033605';

    private const SUFFIX = '5802ID5912Warung Sayur6010Kab. Demak61055956762070703A016304';

    // ---------------------------------------------------------------- CRC16

    public function testCrc16StandardCheckValue(): void
    {
        // Nilai cek standar CRC-16/CCITT-FALSE
        self::assertSame('29B1', Qris::crc16('123456789'));
    }

    public function testCrc16IsPaddedToFourDigits(): void
    {
        self::assertMatchesRegularExpression('/^[0-9A-F]{4}$/', Qris::crc16(''));
        self::assertSame('FFFF', Qris::crc16(''));
    }

    // ------------------------------------------------------------ Validator

    public function testValidStaticQris(): void
    {
        self::assertSame(['valid' => true, 'errors' => []], Qris::validate(self::STATIC_QRIS));
        self::assertTrue(Qris::isValid(self::STATIC_QRIS));
    }

    public function testWhitespaceIsTrimmed(): void
    {
        self::assertTrue(Qris::isValid("  \n" . self::STATIC_QRIS . "\n "));
    }

    public function testEmptyString(): void
    {
        self::assertSame(['QRIS string is empty'], Qris::validate('')['errors']);
    }

    public function testCrcMismatch(): void
    {
        $bad = substr(self::STATIC_QRIS, 0, -4) . '0000';
        self::assertSame(['CRC mismatch: expected 2982, got 0000'], Qris::validate($bad)['errors']);
    }

    public function testRandomTextIsInvalid(): void
    {
        $result = Qris::validate('hello world');
        self::assertFalse($result['valid']);
        self::assertContains('QRIS must start with Payload Format Indicator "000201"', $result['errors']);
    }

    // --------------------------------------------------------------- Parser

    public function testParseStaticQris(): void
    {
        $data = Qris::parse(self::STATIC_QRIS);

        self::assertSame('Warung Sayur', $data->merchantName);
        self::assertSame('Kab. Demak', $data->merchantCity);
        self::assertSame('59567', $data->postalCode);
        self::assertSame('5812', $data->merchantCategoryCode);
        self::assertSame('360', $data->currency);
        self::assertSame('IDR', $data->currencyLabel());
        self::assertSame('ID', $data->countryCode);
        self::assertSame('static', $data->method);
        self::assertTrue($data->isStatic());
        self::assertFalse($data->isDynamic());
        self::assertNull($data->amount);
        self::assertNull($data->amountValue());
        self::assertSame('2982', $data->crc);
    }

    public function testParseMerchantAccountInfo(): void
    {
        $data = Qris::parse(self::STATIC_QRIS);

        self::assertSame('ID.DANA.WWW', $data->issuer());
        self::assertSame('ID1020017611473', $data->nmid());
        self::assertCount(2, $data->merchantAccountInfo);
        self::assertSame('26', $data->merchantAccountInfo[0]['tag']);
        self::assertSame('936009153022591481', $data->merchantAccountInfo[0]['merchantId']);
        self::assertSame('UMI', $data->merchantAccountInfo[0]['merchantCriteria']);
    }

    public function testParseInvalidThrows(): void
    {
        $this->expectException(InvalidQrisException::class);
        Qris::parse('not a qris');
    }

    public function testToArrayAndJson(): void
    {
        $array = Qris::parse(self::STATIC_QRIS)->toArray();

        self::assertSame('Warung Sayur', $array['merchantName']);
        self::assertSame('ID1020017611473', $array['nmid']);
        self::assertSame('00', $array['raw'][0]['tag']);
        self::assertJson(json_encode(Qris::parse(self::STATIC_QRIS)));
    }

    // ------------------------------------------------------------ Converter

    public function testConvertAmountOnly(): void
    {
        self::assertSame(
            self::PREFIX . '40525000' . self::SUFFIX . '0DD1',
            Qris::convert(self::STATIC_QRIS, 25000),
        );
    }

    public function testConvertWithFixedFee(): void
    {
        self::assertSame(
            self::PREFIX . '4052500055020256041000' . self::SUFFIX . '741A',
            Qris::convert(self::STATIC_QRIS, 25000, Qris::FEE_FIXED, 1000),
        );
    }

    public function testConvertWithPercentageFee(): void
    {
        self::assertSame(
            self::PREFIX . '40615000055020357032.5' . self::SUFFIX . '4343',
            Qris::convert(self::STATIC_QRIS, 150000, Qris::FEE_PERCENTAGE, 2.5),
        );
    }

    public function testConvertedQrisIsValidAndDynamic(): void
    {
        $dynamic = Qris::convert(self::STATIC_QRIS, 7000, Qris::FEE_FIXED, 500);
        $data = Qris::parse($dynamic);

        self::assertTrue(Qris::isValid($dynamic));
        self::assertTrue($data->isDynamic());
        self::assertSame('7000', $data->amount);
        self::assertSame(7000.0, $data->amountValue());
        self::assertSame('fixed', $data->tipIndicator);
        self::assertSame('500', $data->tipFixed);
        self::assertSame('Warung Sayur', $data->merchantName);
    }

    public function testConvertedQrisKeepStatic(): void
    {
        $converted = Qris::convert(self::STATIC_QRIS, 50000, keepStatic: true);
        $data = Qris::parse($converted);

        self::assertTrue(Qris::isValid($converted));
        self::assertTrue($data->isStatic());
        self::assertFalse($data->isDynamic());
        self::assertSame('50000', $data->amount);
        self::assertSame(50000.0, $data->amountValue());
        self::assertSame('Warung Sayur', $data->merchantName);
    }

    public function testReconvertReplacesAmountAndFee(): void
    {
        $first = Qris::convert(self::STATIC_QRIS, 7000, Qris::FEE_PERCENTAGE, 3);
        self::assertSame(self::PREFIX . '4049999' . self::SUFFIX . 'CCD0', Qris::convert($first, 9999));
    }

    public function testZeroFeeIsIgnored(): void
    {
        self::assertSame(
            Qris::convert(self::STATIC_QRIS, 25000),
            Qris::convert(self::STATIC_QRIS, 25000, Qris::FEE_FIXED, 0),
        );
    }

    public function testConvertInvalidQrisThrowsWithErrors(): void
    {
        try {
            Qris::convert(substr(self::STATIC_QRIS, 0, -4) . '0000', 10000);
            self::fail('Exception expected');
        } catch (InvalidQrisException $e) {
            self::assertSame(['CRC mismatch: expected 2982, got 0000'], $e->getErrors());
        }
    }

    public function testZeroAmountThrows(): void
    {
        $this->expectException(InvalidQrisException::class);
        Qris::convert(self::STATIC_QRIS, 0);
    }

    public function testUnknownFeeTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Qris::convert(self::STATIC_QRIS, 10000, 'tip', 100);
    }

    public function testNegativeFeeThrows(): void
    {
        $this->expectException(InvalidQrisException::class);
        Qris::convert(self::STATIC_QRIS, 10000, Qris::FEE_FIXED, -1);
    }

    public function testFormatNumberMatchesJavaScript(): void
    {
        self::assertSame('25000', Converter::formatNumber(25000));
        self::assertSame('25000', Converter::formatNumber(25000.0));
        self::assertSame('2.5', Converter::formatNumber(2.5));
        self::assertSame('0.75', Converter::formatNumber(0.75));
    }
}
