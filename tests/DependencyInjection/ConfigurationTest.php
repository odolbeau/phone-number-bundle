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
use Misd\PhoneNumberBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider configurationDataProvider
     *
     * @param array<mixed> $configs
     * @param array<mixed> $expected
     */
    public function testConfiguration(array $configs, array $expected): void
    {
        $processor = new Processor();
        $result = $processor->processConfiguration(new Configuration(), $configs);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<array{array<mixed>, array<mixed>}>
     */
    public function configurationDataProvider(): iterable
    {
        // Empty Configuration
        yield [[], [
            'twig' => [
                'enabled' => true,
            ],
            'form' => [
                'enabled' => true,
            ],
            'serializer' => [
                'enabled' => true,
                'default_region' => 'ZZ',
                'format' => PhoneNumberFormat::E164,
            ],
            'validator' => [
                'enabled' => true,
                'default_region' => 'ZZ',
                'format' => PhoneNumberFormat::INTERNATIONAL,
            ],
        ]];

        // Everything deactivated
        yield [[
            'misd_phone_number' => [
                'twig' => false,
                'form' => false,
                'serializer' => false,
                'validator' => false,
            ],
        ], [
            'twig' => [
                'enabled' => false,
            ],
            'form' => [
                'enabled' => false,
            ],
            'serializer' => [
                'enabled' => false,
                'default_region' => 'ZZ',
                'format' => PhoneNumberFormat::E164,
            ],
            'validator' => [
                'enabled' => false,
                'default_region' => 'ZZ',
                'format' => PhoneNumberFormat::INTERNATIONAL,
            ],
        ]];

        // With custom configuration
        yield [[
            'misd_phone_number' => [
                'twig' => [
                    'enabled' => false,
                ],
                'form' => [
                    'enabled' => false,
                ],
                'serializer' => [
                    'enabled' => false,
                    'default_region' => 'GB',
                    'format' => PhoneNumberFormat::E164,
                ],
                'validator' => [
                    'enabled' => false,
                    'default_region' => 'GB',
                    'format' => PhoneNumberFormat::INTERNATIONAL,
                ],
            ],
        ], [
            'twig' => [
                'enabled' => false,
            ],
            'form' => [
                'enabled' => false,
            ],
            'serializer' => [
                'enabled' => false,
                'default_region' => 'GB',
                'format' => PhoneNumberFormat::E164,
            ],
            'validator' => [
                'enabled' => false,
                'default_region' => 'GB',
                'format' => PhoneNumberFormat::INTERNATIONAL,
            ],
        ]];

        // Same with BC (int)
        yield [[
            'misd_phone_number' => [
                'twig' => [
                    'enabled' => false,
                ],
                'form' => [
                    'enabled' => false,
                ],
                'serializer' => [
                    'enabled' => false,
                    'default_region' => 'GB',
                    'format' => 0,
                ],
                'validator' => [
                    'enabled' => false,
                    'default_region' => 'GB',
                    'format' => 1,
                ],
            ],
        ], [
            'twig' => [
                'enabled' => false,
            ],
            'form' => [
                'enabled' => false,
            ],
            'serializer' => [
                'enabled' => false,
                'default_region' => 'GB',
                'format' => PhoneNumberFormat::E164,
            ],
            'validator' => [
                'enabled' => false,
                'default_region' => 'GB',
                'format' => PhoneNumberFormat::INTERNATIONAL,
            ],
        ]];

        // Same with BC (string)
        yield [[
            'misd_phone_number' => [
                'twig' => [
                    'enabled' => false,
                ],
                'form' => [
                    'enabled' => false,
                ],
                'serializer' => [
                    'enabled' => false,
                    'default_region' => 'GB',
                    'format' => 'E164',
                ],
                'validator' => [
                    'enabled' => false,
                    'default_region' => 'GB',
                    'format' => 'INTERNATIONAL',
                ],
            ],
        ], [
            'twig' => [
                'enabled' => false,
            ],
            'form' => [
                'enabled' => false,
            ],
            'serializer' => [
                'enabled' => false,
                'default_region' => 'GB',
                'format' => PhoneNumberFormat::E164,
            ],
            'validator' => [
                'enabled' => false,
                'default_region' => 'GB',
                'format' => PhoneNumberFormat::INTERNATIONAL,
            ],
        ]];
    }
}
