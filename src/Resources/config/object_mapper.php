<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Misd\PhoneNumberBundle\ObjectMapper\PhoneNumberMappingMetadataFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('misd_phone_number.object_mapper.metadata_factory', PhoneNumberMappingMetadataFactory::class)
            // Dropped if the application doesn't use the ObjectMapper.
            ->decorate('object_mapper.metadata_factory', invalidBehavior: ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([
                service('.inner'),
                param('misd_phone_number.object_mapper.format'),
                param('misd_phone_number.object_mapper.default_region'),
            ]);
};
