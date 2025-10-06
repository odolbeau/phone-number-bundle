<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper;
use Misd\PhoneNumberBundle\Twig\Extension\PhoneNumberHelperExtension;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->set(PhoneNumberHelper::class)
            ->args([
                service(PhoneNumberUtil::class),
                param('misd_phone_number.twig.default_region'),
                param('misd_phone_number.twig.format'),
            ])

        ->set(PhoneNumberHelperExtension::class)
            ->tag('twig.extension')
            ->args([
                service(PhoneNumberHelper::class),
            ]);
};
