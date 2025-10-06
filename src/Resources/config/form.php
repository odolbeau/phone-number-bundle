<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Misd\PhoneNumberBundle\Form\Extension\PhoneNumberTypeEqualityExtension;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->set(PhoneNumberType::class)
            ->tag('form.type', ['alias' => 'phone_number'])

        ->set(PhoneNumberTypeEqualityExtension::class)
            ->tag('form.extension', ['extended_type' => PhoneNumberType::class])
            ->args([
                service('form.property_accessor'),
            ]);
};
