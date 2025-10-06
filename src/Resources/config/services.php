<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberToTimeZonesMapper;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\ShortNumberInfo;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->set(PhoneNumberUtil::class)
            ->factory([PhoneNumberUtil::class, 'getInstance'])

        ->set(PhoneNumberOfflineGeocoder::class)
            ->factory([PhoneNumberOfflineGeocoder::class, 'getInstance'])

        ->set(ShortNumberInfo::class)
            ->factory([ShortNumberInfo::class, 'getInstance'])

        ->set(PhoneNumberToCarrierMapper::class)
            ->factory([PhoneNumberToCarrierMapper::class, 'getInstance'])

        ->set(PhoneNumberToTimeZonesMapper::class)
            ->factory([PhoneNumberToTimeZonesMapper::class, 'getInstance'])
    ;
};
