<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper;

use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\ObjectMapper\PhoneNumberTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * Contact mapped for output: $phone and $fax rely on the automatic
 * conversion, $formatted and $national declare their transform.
 */
#[Map(source: Contact::class)]
final class ContactOutput
{
    public function __construct(
        public readonly string $phone,
        public readonly ?string $fax,
        public readonly string $name,
        #[Map(source: 'phone', transform: new PhoneNumberTransformer(PhoneNumberFormat::INTERNATIONAL))]
        public readonly string $formatted,
        #[Map(source: 'phone', transform: new PhoneNumberTransformer(PhoneNumberFormat::NATIONAL))]
        public readonly string $national,
    ) {
    }
}
