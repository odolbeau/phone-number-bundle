<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Misd\PhoneNumberBundle\Serializer\Normalizer\PhoneNumberNormalizer;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->set(PhoneNumberNormalizer::class)
            ->tag('serializer.normalizer')
            ->args([
                service('libphonenumber\PhoneNumberUtil'),
                param('misd_phone_number.serializer.default_region'),
                param('misd_phone_number.serializer.format'),
            ]);
};
