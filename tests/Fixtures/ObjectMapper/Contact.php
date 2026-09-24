<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper;

use libphonenumber\PhoneNumber;

final class Contact
{
    public function __construct(
        public readonly PhoneNumber $phone,
        public readonly ?PhoneNumber $fax = null,
        public readonly string $name = '',
    ) {
    }
}
