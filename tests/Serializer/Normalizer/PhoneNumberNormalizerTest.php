<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony2 PhoneNumberBundle.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\PhoneNumberBundle\Tests\Serializer\Normalizer;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Serializer\Normalizer\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Phone number serialization test.
 */
class PhoneNumberNormalizerTest extends TestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        if (!class_exists(Serializer::class)) {
            $this->markTestSkipped('The Symfony Serializer is not available.');
        }
    }

    public function testSupportNormalization(): void
    {
        $normalizer = new PhoneNumberNormalizer($this->prophesize(PhoneNumberUtil::class)->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new PhoneNumber()));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalize(): void
    {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setRawInput('+33193166989');

        $phoneNumberUtil = $this->prophesize(PhoneNumberUtil::class);
        $phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::E164)->shouldBeCalledTimes(1)->willReturn('+33193166989');

        $normalizer = new PhoneNumberNormalizer($phoneNumberUtil->reveal());

        $this->assertEquals('+33193166989', $normalizer->normalize($phoneNumber));
    }

    public function testSupportDenormalization(): void
    {
        $normalizer = new PhoneNumberNormalizer($this->prophesize(PhoneNumberUtil::class)->reveal());

        $this->assertTrue($normalizer->supportsDenormalization('+33193166989', 'libphonenumber\PhoneNumber'));
        $this->assertFalse($normalizer->supportsDenormalization(new PhoneNumber(), 'libphonenumber\PhoneNumber'));
        $this->assertFalse($normalizer->supportsDenormalization('+33193166989', 'stdClass'));
    }

    public function testDenormalize(): void
    {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setRawInput('+33193166989');

        $phoneNumberUtil = $this->prophesize(PhoneNumberUtil::class);
        $phoneNumberUtil->parse('+33193166989', PhoneNumberUtil::UNKNOWN_REGION)->shouldBeCalledTimes(1)->willReturn($phoneNumber);

        $normalizer = new PhoneNumberNormalizer($phoneNumberUtil->reveal());

        $this->assertSame($phoneNumber, $normalizer->denormalize('+33193166989', 'libphonenumber\PhoneNumber'));
    }

    public function testItDenormalizeNullToNull(): void
    {
        $phoneNumberUtil = $this->prophesize(PhoneNumberUtil::class);
        $phoneNumberUtil->parse(Argument::cetera())->shouldNotBeCalled();

        $normalizer = new PhoneNumberNormalizer($phoneNumberUtil->reveal());

        $this->assertNull($normalizer->denormalize(null, 'libphonenumber\PhoneNumber'));
    }

    public function testInvalidDateThrowException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $phoneNumberUtil = $this->prophesize(PhoneNumberUtil::class);
        $phoneNumberUtil
            ->parse('invalid phone number', PhoneNumberUtil::UNKNOWN_REGION)
            ->shouldBeCalledTimes(1)
            ->willThrow(new NumberParseException(NumberParseException::INVALID_COUNTRY_CODE, ''))
        ;

        $normalizer = new PhoneNumberNormalizer($phoneNumberUtil->reveal());
        $normalizer->denormalize('invalid phone number', 'libphonenumber\PhoneNumber');
    }

    /**
     * Ensure BC is respected.
     */
    public function testStillSupportsIntAsFormatArgument(): void
    {
        $normalizer = new PhoneNumberNormalizer($this->prophesize(PhoneNumberUtil::class)->reveal(), format: 1);

        $this->assertTrue($normalizer->supportsNormalization(new PhoneNumber()));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testPartialDenormalizationOnInvalidPhoneNumber(): void
    {
        $json = json_encode([
            'phone' => '+1234567890123456789012345678901234567890', // too long
        ]);

        try {
            $serializer = new Serializer([
                new PhoneNumberNormalizer(PhoneNumberUtil::getInstance(), 'FR'),
                new ObjectNormalizer(),
            ], [
                new JsonEncoder(),
            ]);

            $serializer->deserialize($json, TestDto::class, 'json', [
                'collect_denormalization_errors' => true,
            ]);

            $this->fail('Expected PartialDenormalizationException not thrown.');
        } catch (PartialDenormalizationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);

            $error = $errors[0];
            $this->assertSame(class_exists(UnsupportedFormatException::class) ? 'phone' : null, $error->getPath());
            $this->assertStringContainsString('too long', $error->getMessage());

            $data = $e->getData();
            $this->assertInstanceOf(TestDto::class, $data);
        }
    }
}

class TestDto
{
    public function __construct(
        public ?PhoneNumber $phone,
    ) {
    }
}
