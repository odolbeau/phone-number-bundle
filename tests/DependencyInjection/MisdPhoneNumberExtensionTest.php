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

namespace Misd\PhoneNumberBundle\Tests\DependencyInjection;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\DependencyInjection\MisdPhoneNumberExtension;
use Misd\PhoneNumberBundle\ObjectMapper\PhoneNumberMappingMetadataFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * Bundle extension test.
 */
class MisdPhoneNumberExtensionTest extends TestCase
{
    private TaggedContainerInterface $container;

    public function testLoad(): void
    {
        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();

        $extension->load([], $this->container);

        $this->assertTrue($this->container->has('libphonenumber\PhoneNumberUtil'));
        if (class_exists('libphonenumber\geocoding\PhoneNumberOfflineGeocoder') && \extension_loaded('intl')) {
            $this->assertTrue($this->container->has('libphonenumber\geocoding\PhoneNumberOfflineGeocoder'));
        }
        if (class_exists('libphonenumber\ShortNumberInfo')) {
            $this->assertTrue($this->container->has('libphonenumber\ShortNumberInfo'));
        }
        if (class_exists('libphonenumber\PhoneNumberToCarrierMapper') && \extension_loaded('intl')) {
            $this->assertTrue($this->container->has('libphonenumber\PhoneNumberToCarrierMapper'));
        }
        if (class_exists('libphonenumber\PhoneNumberToTimeZonesMapper')) {
            $this->assertTrue($this->container->has('libphonenumber\PhoneNumberToTimeZonesMapper'));
        }
        $this->assertTrue($this->container->has('Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper'));
        $this->assertTrue($this->container->has('Misd\PhoneNumberBundle\Form\Type\PhoneNumberType'));

        $services = $this->container->findTaggedServiceIds('form.type');
        $this->assertArrayHasKey('Misd\PhoneNumberBundle\Form\Type\PhoneNumberType', $services);
        $this->assertContains(['alias' => 'phone_number'], $services['Misd\PhoneNumberBundle\Form\Type\PhoneNumberType']);

        $services = $this->container->findTaggedServiceIds('validator.constraint_validator');
        $this->assertArrayHasKey('Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumberValidator', $services);

        $this->assertSame(PhoneNumberUtil::UNKNOWN_REGION, $this->container->getParameter('misd_phone_number.validator.default_region'));
    }

    public function testDisabledServices(): void
    {
        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();
        $extension->load([
            'misd_phone_number' => [
                'twig' => false,
                'form' => false,
                'serializer' => false,
                'validator' => false,
                'object_mapper' => false,
            ],
        ], $this->container);

        $this->assertTrue($this->container->has('libphonenumber\PhoneNumberUtil'));

        $this->assertFalse($this->container->has('Misd\PhoneNumberBundle\Twig\Extension\PhoneNumberHelperExtension'));
        $this->assertFalse($this->container->has('Misd\PhoneNumberBundle\Form\Type\PhoneNumberType'));
        $this->assertFalse($this->container->has('Misd\PhoneNumberBundle\Serializer\Normalizer\PhoneNumberNormalizer'));
        $this->assertFalse($this->container->has('Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumberValidator'));
        $this->assertFalse($this->container->has('misd_phone_number.object_mapper.metadata_factory'));
    }

    public function testValidatorParameters(): void
    {
        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();
        $extension->load([
            'misd_phone_number' => [
                'validator' => [
                    'default_region' => 'GB',
                    'format' => PhoneNumberFormat::E164,
                ],
            ],
        ], $this->container);

        $this->assertSame('GB', $this->container->getParameter('misd_phone_number.validator.default_region'));
        $this->assertSame(PhoneNumberFormat::E164, $this->container->getParameter('misd_phone_number.validator.format'));
    }

    public function testNormalizerParameters(): void
    {
        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();
        $extension->load([
            'misd_phone_number' => [
                'serializer' => [
                    'default_region' => 'FR',
                    'format' => PhoneNumberFormat::INTERNATIONAL,
                ],
            ],
        ], $this->container);

        $this->assertSame('FR', $this->container->getParameter('misd_phone_number.serializer.default_region'));
        $this->assertSame(PhoneNumberFormat::INTERNATIONAL, $this->container->getParameter('misd_phone_number.serializer.format'));
    }

    public function testObjectMapperMetadataFactory(): void
    {
        if (!interface_exists(ObjectMapperInterface::class)) {
            $this->markTestSkipped('The Symfony ObjectMapper is not available.');
        }

        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();
        $extension->load([
            'misd_phone_number' => [
                'object_mapper' => [
                    'default_region' => 'FR',
                    'format' => PhoneNumberFormat::INTERNATIONAL,
                ],
            ],
        ], $this->container);

        $definition = $this->container->getDefinition('misd_phone_number.object_mapper.metadata_factory');
        $this->assertSame(PhoneNumberMappingMetadataFactory::class, $definition->getClass());
        $this->assertSame(['object_mapper.metadata_factory', null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE], $definition->getDecoratedService());
        $this->assertSame('FR', $this->container->getParameter('misd_phone_number.object_mapper.default_region'));
        $this->assertSame(PhoneNumberFormat::INTERNATIONAL, $this->container->getParameter('misd_phone_number.object_mapper.format'));
    }

    public function testObjectMapperMetadataFactoryDecoratesTheObjectMapperOne(): void
    {
        if (!interface_exists(ObjectMapperInterface::class)) {
            $this->markTestSkipped('The Symfony ObjectMapper is not available.');
        }

        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();
        $this->container->register('object_mapper.metadata_factory', ReflectionObjectMapperMetadataFactory::class)->setPublic(true);
        $extension->load(['misd_phone_number' => ['twig' => false, 'form' => false, 'serializer' => false, 'validator' => false]], $this->container);
        $this->container->compile();

        $this->assertInstanceOf(PhoneNumberMappingMetadataFactory::class, $this->container->get('object_mapper.metadata_factory'));
    }

    public function testObjectMapperMetadataFactoryIsDroppedWhenTheObjectMapperIsNotWired(): void
    {
        $extension = new MisdPhoneNumberExtension();
        $this->container = new ContainerBuilder();
        $extension->load(['misd_phone_number' => ['object_mapper' => true]], $this->container);

        // Would throw if the decorated service were required.
        $this->container->compile();

        $this->assertFalse($this->container->hasDefinition('misd_phone_number.object_mapper.metadata_factory'));
    }
}
