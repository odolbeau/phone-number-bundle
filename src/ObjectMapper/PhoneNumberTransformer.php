<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\ObjectMapper;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Exception\InvalidArgumentException;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * Converts phone numbers when mapping objects with the Symfony ObjectMapper,
 * both ways: a PhoneNumber becomes a string in the given format, and a string
 * is parsed into a PhoneNumber (numbers without a country code are read as
 * belonging to the given default region).
 *
 * Instantiate it right in the attribute:
 *
 *     #[Map(source: 'phoneNumber', transform: new PhoneNumberTransformer(PhoneNumberFormat::INTERNATIONAL))]
 *     public string $phoneNumber;
 *
 * @implements TransformCallableInterface<object, object>
 */
final class PhoneNumberTransformer implements TransformCallableInterface
{
    public function __construct(
        private readonly PhoneNumberFormat $format = PhoneNumberFormat::E164,
        private readonly string $defaultRegion = PhoneNumberUtil::UNKNOWN_REGION,
        // Not stored by default: the ObjectMapper cache warmer exports mapping
        // metadata, this transformer included, to a PHP file.
        private readonly ?PhoneNumberUtil $phoneNumberUtil = null,
    ) {
    }

    public function __invoke(mixed $value, object $source, ?object $target): PhoneNumber|string|null
    {
        $phoneNumberUtil = $this->phoneNumberUtil ?? PhoneNumberUtil::getInstance();

        if (null === $value) {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return $phoneNumberUtil->format($value, $this->format);
        }

        if (\is_string($value)) {
            try {
                return $phoneNumberUtil->parse($value, $this->defaultRegion);
            } catch (NumberParseException $e) {
                throw new MappingTransformException(\sprintf('Cannot parse "%s" as a phone number: %s', $value, $e->getMessage()), 0, $e);
            }
        }

        throw new InvalidArgumentException(\sprintf('Expected a "%s" or a string, got "%s".', PhoneNumber::class, get_debug_type($value)));
    }
}
