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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Bundle extension.
 */
class MisdPhoneNumberExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
        if ($config['twig']['enabled']) {
            $loader->load('twig.php');

            $container->setParameter('misd_phone_number.twig.default_region', $config['twig']['default_region']);
            $container->setParameter('misd_phone_number.twig.format', $config['twig']['format']);
        }
        if ($config['form']['enabled']) {
            $loader->load('form.php');
        }
        if ($config['serializer']['enabled']) {
            $loader->load('serializer.php');

            $container->setParameter('misd_phone_number.serializer.default_region', $config['serializer']['default_region']);
            $container->setParameter('misd_phone_number.serializer.format', $config['serializer']['format']);
        }
        if ($config['validator']['enabled']) {
            $loader->load('validator.php');

            $container->setParameter('misd_phone_number.validator.default_region', $config['validator']['default_region']);
            $container->setParameter('misd_phone_number.validator.format', $config['validator']['format']);
        }
    }
}
