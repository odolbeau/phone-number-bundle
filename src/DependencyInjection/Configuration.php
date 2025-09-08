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

namespace Misd\PhoneNumberBundle\DependencyInjection;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('misd_phone_number');
        $rootNode = $treeBuilder->getRootNode();

        $normalizer = function ($value) {
            if (\is_bool($value)) {
                return [
                    'enabled' => $value,
                ];
            }

            return $value;
        };

        $normalizeFormat = static function (int|string $value): PhoneNumberFormat {
            trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Passing a scalar for the "format" configuration key is deprecated, pass a libphonenumber\PhoneNumberFormat instance instead.');

            if (\is_int($value)) {
                return PhoneNumberFormat::from($value);
            }

            /** @var PhoneNumberFormat */
            $format = (new \ReflectionEnumBackedCase(PhoneNumberFormat::class, $value))->getValue();

            return $format;
        };

        $rootNode
            ->children()
                ->arrayNode('twig')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()->always($normalizer)->end()
                    ->children()
                        ->scalarNode('enabled')
                            ->defaultValue(class_exists(TwigBundle::class))
                        ->end()
                        ->scalarNode('default_region')
                            ->defaultValue(PhoneNumberUtil::UNKNOWN_REGION)
                        ->end()
                        ->enumNode('format')
                            ->values(PhoneNumberFormat::cases())
                            ->defaultValue(PhoneNumberFormat::E164)
                            ->beforeNormalization()
                                ->ifTrue(fn ($value) => \is_string($value) || \is_int($value))
                                ->then($normalizeFormat)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()->always($normalizer)->end()
                    ->children()
                        ->scalarNode('enabled')
                            ->defaultValue(interface_exists(FormTypeInterface::class))
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('serializer')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()->always($normalizer)->end()
                    ->children()
                        ->scalarNode('enabled')
                            ->defaultValue(interface_exists(NormalizerInterface::class))
                        ->end()
                        ->scalarNode('default_region')
                            ->defaultValue(PhoneNumberUtil::UNKNOWN_REGION)
                        ->end()
                        ->enumNode('format')
                            ->values(PhoneNumberFormat::cases())
                            ->defaultValue(PhoneNumberFormat::E164)
                            ->beforeNormalization()
                                ->ifTrue(fn ($value) => \is_string($value) || \is_int($value))
                                ->then($normalizeFormat)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('validator')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()->always($normalizer)->end()
                    ->children()
                        ->scalarNode('enabled')->defaultValue(interface_exists(ValidatorInterface::class))->end()
                        ->scalarNode('default_region')
                            ->defaultValue(PhoneNumberUtil::UNKNOWN_REGION)
                        ->end()
                        ->enumNode('format')
                            // The difference between serializer and validator is historical, they are here to keep the BC
                            ->values(PhoneNumberFormat::cases())
                            ->defaultValue(PhoneNumberFormat::INTERNATIONAL)
                            ->beforeNormalization()
                                ->ifTrue(fn ($value) => \is_string($value) || \is_int($value))
                                ->then($normalizeFormat)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
