<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\ObjectMapper;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\ObjectMapper\PhoneNumberMappingMetadataFactory;
use Misd\PhoneNumberBundle\ObjectMapper\PhoneNumberTransformer;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\Contact;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\ContactInput;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\ContactOutput;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\SourceMappedContact;
use Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper\SourceMappedContactOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\MappingAwareTransformCallableInterface;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapper;

class PhoneNumberMappingMetadataFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(ObjectMapper::class)) {
            $this->markTestSkipped('The Symfony ObjectMapper is not available.');
        }
    }

    public function testItFormatsAPhoneNumberMappedToAString(): void
    {
        $this->requiresTypedMappingContext();

        $output = $this->createObjectMapper()->map($this->contact(), ContactOutput::class);

        $this->assertSame('+441234567890', $output->phone);
        $this->assertNull($output->fax);
        $this->assertSame('Ada', $output->name);
    }

    public function testItFormatsWhenTheMappingIsDeclaredOnTheSource(): void
    {
        $this->requiresTypedMappingContext();

        $output = $this->createObjectMapper()->map(new SourceMappedContact($this->phoneNumber()));

        $this->assertInstanceOf(SourceMappedContactOutput::class, $output);
        $this->assertSame('+441234567890', $output->mobile);
    }

    public function testItParsesAStringMappedToAPhoneNumber(): void
    {
        $this->requiresTypedMappingContext();

        $contact = $this->createObjectMapper()->map(new ContactInput('01234 567890', 'Ada'), Contact::class);

        $this->assertEquals($this->phoneNumber(), $contact->phone);
        $this->assertSame('Ada', $contact->name);
    }

    public function testItLetsAnExplicitTransformWin(): void
    {
        $this->requiresTypedMappingContext();

        $output = $this->createObjectMapper()->map($this->contact(), ContactOutput::class);

        $this->assertSame('+44 1234 567890', $output->formatted);
        $this->assertSame('01234 567890', $output->national);
    }

    public function testItRejectsAStringThatIsNotAPhoneNumber(): void
    {
        $this->requiresTypedMappingContext();

        $this->expectException(MappingTransformException::class);

        $this->createObjectMapper()->map(new ContactInput('not a phone number', 'Ada'), Contact::class);
    }

    public function testItLeavesTheMappingUntouchedWithoutTheMappedClasses(): void
    {
        $inner = new ReflectionObjectMapperMetadataFactory();
        $factory = new PhoneNumberMappingMetadataFactory($inner);
        $output = new ContactOutput('', null, '', '', '');

        // Symfony < 8.2 doesn't pass the mapped classes in the context.
        $this->assertEquals($inner->create($output, 'phone'), $factory->create($output, 'phone'));
        $this->assertEquals($inner->create($output, 'formatted'), $factory->create($output, 'formatted'));
    }

    public function testItLeavesOtherTypesUntouched(): void
    {
        $factory = new PhoneNumberMappingMetadataFactory(new ReflectionObjectMapperMetadataFactory());
        $context = ['source' => Contact::class, 'target' => ContactOutput::class];

        $this->assertSame([], $factory->create($this->contact(), 'name', $context));
        $this->assertSame([], $factory->create($this->contact(), 'unknown', $context));
    }

    public function testItKeepsTheTargetClassAMappingIsRestrictedTo(): void
    {
        if (!property_exists(Mapping::class, 'targetClass')) {
            $this->markTestSkipped('Mappings are restricted to a target class as of Symfony 8.1.');
        }

        // Built through reflection: the argument doesn't exist before Symfony 8.1.
        $restricted = (new \ReflectionClass(Mapping::class))->newInstance('phone', 'phone', null, null, ContactOutput::class);
        $inner = new class($restricted) implements ObjectMapperMetadataFactoryInterface {
            public function __construct(private readonly Mapping $mapping)
            {
            }

            public function create(object $object, ?string $property = null, array $context = []): array
            {
                return [$this->mapping];
            }
        };

        $mappings = (new PhoneNumberMappingMetadataFactory($inner))->create($this->contact(), 'phone', ['source' => Contact::class, 'target' => ContactOutput::class]);

        $this->assertCount(1, $mappings);
        $this->assertInstanceOf(PhoneNumberTransformer::class, $mappings[0]->transform);
        $this->assertSame(ContactOutput::class, get_object_vars($mappings[0])['targetClass'] ?? null);
    }

    private function requiresTypedMappingContext(): void
    {
        if (!interface_exists(MappingAwareTransformCallableInterface::class)) {
            $this->markTestSkipped('The ObjectMapper passes the mapped classes to metadata factories as of Symfony 8.2.');
        }
    }

    private function createObjectMapper(): ObjectMapper
    {
        return new ObjectMapper(new PhoneNumberMappingMetadataFactory(new ReflectionObjectMapperMetadataFactory(), PhoneNumberFormat::E164, 'GB'));
    }

    private function contact(): Contact
    {
        return new Contact($this->phoneNumber(), null, 'Ada');
    }

    private function phoneNumber(): PhoneNumber
    {
        return PhoneNumberUtil::getInstance()->parse('+441234567890', PhoneNumberUtil::UNKNOWN_REGION);
    }
}
