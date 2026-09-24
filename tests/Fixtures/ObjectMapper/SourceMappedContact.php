<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper;

use libphonenumber\PhoneNumber;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * A contact whose mapping is declared on the source side.
 */
#[Map(target: SourceMappedContactOutput::class)]
final class SourceMappedContact
{
    public function __construct(
        #[Map(target: 'mobile')]
        public readonly PhoneNumber $phone,
    ) {
    }
}
