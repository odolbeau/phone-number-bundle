<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\ObjectMapper;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Exception\InvalidArgumentException;
use Misd\PhoneNumberBundle\ObjectMapper\PhoneNumberTransformer;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\Contact;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\ContactOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\ObjectMapper;

class PhoneNumberTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(ObjectMapper::class)) {
            $this->markTestSkipped('The Symfony ObjectMapper is not available.');
        }
    }

    public function testItFormatsInE164ByDefault(): void
    {
        $this->assertSame('+441234567890', (new PhoneNumberTransformer())($this->phoneNumber(), new \stdClass(), null));
    }

    public function testItFormatsInTheGivenFormat(): void
    {
        $transformer = new PhoneNumberTransformer(PhoneNumberFormat::INTERNATIONAL);

        $this->assertSame('+44 1234 567890', $transformer($this->phoneNumber(), new \stdClass(), null));
    }

    public function testItUsesTheGivenPhoneNumberUtil(): void
    {
        $transformer = new PhoneNumberTransformer(PhoneNumberFormat::NATIONAL, phoneNumberUtil: PhoneNumberUtil::getInstance());

        $this->assertSame('01234 567890', $transformer($this->phoneNumber(), new \stdClass(), null));
    }

    public function testItLeavesAMissingNumberEmpty(): void
    {
        $this->assertNull((new PhoneNumberTransformer())(null, new \stdClass(), null));
    }

    public function testItParsesAStringWithACountryCode(): void
    {
        $phoneNumber = (new PhoneNumberTransformer())('+44 1234 567890', new \stdClass(), null);

        $this->assertEquals($this->phoneNumber(), $phoneNumber);
    }

    public function testItParsesAStringWithoutACountryCodeInTheDefaultRegion(): void
    {
        $phoneNumber = (new PhoneNumberTransformer(defaultRegion: 'GB'))('01234 567890', new \stdClass(), null);

        $this->assertEquals($this->phoneNumber(), $phoneNumber);
    }

    public function testItRejectsAStringThatIsNotAPhoneNumber(): void
    {
        $this->expectException(MappingTransformException::class);

        (new PhoneNumberTransformer())('not a phone number', new \stdClass(), null);
    }

    public function testItRejectsANumberWithoutACountryCodeWhenThereIsNoDefaultRegion(): void
    {
        $this->expectException(MappingTransformException::class);

        (new PhoneNumberTransformer())('01234 567890', new \stdClass(), null);
    }

    public function testItRejectsAnythingElse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PhoneNumberTransformer())(441234567890, new \stdClass(), null);
    }

    public function testItMapsAnObject(): void
    {
        $output = (new ObjectMapper())->map(new Contact($this->phoneNumber(), name: 'Ada'), ContactOutput::class);

        $this->assertSame('+44 1234 567890', $output->formatted);
        $this->assertSame('01234 567890', $output->national);
    }

    private function phoneNumber(): PhoneNumber
    {
        return PhoneNumberUtil::getInstance()->parse('+441234567890', PhoneNumberUtil::UNKNOWN_REGION);
    }
}
