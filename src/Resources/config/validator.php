<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumberValidator;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->set(PhoneNumberValidator::class)
            ->tag('validator.constraint_validator')
            ->call('setPropertyAccessor', [
                service('property_accessor')->ignoreOnInvalid(),
            ])
            ->args([
                service(PhoneNumberUtil::class),
                param('misd_phone_number.validator.default_region'),
                param('misd_phone_number.validator.format'),
            ]);
};
