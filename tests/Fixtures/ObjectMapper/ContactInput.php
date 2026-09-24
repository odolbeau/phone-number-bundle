<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: Contact::class)]
final class ContactInput
{
    public function __construct(
        public readonly string $phone,
        public readonly string $name,
    ) {
    }
}
