<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony2 PhoneNumberBundle.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\PhoneNumberBundle\Templating\Helper;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Exception\InvalidArgumentException;

/**
 * Phone number templating helper.
 */
class PhoneNumberHelper
{
    protected PhoneNumberUtil $phoneNumberUtil;

    public function __construct(PhoneNumberUtil $phoneNumberUtil)
    {
        $this->phoneNumberUtil = $phoneNumberUtil;
    }

    public function format(PhoneNumber|string $phoneNumber, string|int|PhoneNumberFormat $format = PhoneNumberFormat::INTERNATIONAL): string
    {
        $phoneNumber = $this->getPhoneNumber($phoneNumber);

        if (true === \is_string($format)) {
            trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Passing a string to the "format" parameter is deprecated, pass a libphonenumber\PhoneNumberFormat instance instead.');

            $constant = '\libphonenumber\PhoneNumberFormat::'.$format;
            if (false === \defined($constant)) {
                throw new InvalidArgumentException('The format must be either a constant value or name in libphonenumber\PhoneNumberFormat');
            }

            $format = \constant('\libphonenumber\PhoneNumberFormat::'.$format);
        } elseif (true === \is_int($format)) {
            trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Passing a int to the "format" parameter is deprecated, pass a libphonenumber\PhoneNumberFormat instance instead.');

            try {
                $format = PhoneNumberFormat::from($format);
            } catch (\ValueError $error) {
                throw new InvalidArgumentException('The format must be either a constant value or enum in libphonenumber\PhoneNumberFormat');
            }
        }

        return $this->phoneNumberUtil->format($phoneNumber, $format);
    }

    /**
     * Formats this phone number for out-of-country dialing purposes.
     *
     * @param PhoneNumber|string $phoneNumber phone number
     * @param string|null        $regionCode  The ISO 3166-1 alpha-2 country code
     */
    public function formatOutOfCountryCallingNumber(PhoneNumber|string $phoneNumber, ?string $regionCode): string
    {
        $phoneNumber = $this->getPhoneNumber($phoneNumber);

        return $this->phoneNumberUtil->formatOutOfCountryCallingNumber($phoneNumber, $regionCode);
    }

    /**
     * @param PhoneNumber|string $phoneNumber phone number
     * @param int|string         $type        phoneNumberType, or PhoneNumberType constant name
     *
     * @throws InvalidArgumentException if type argument is invalid
     */
    public function isType($phoneNumber, $type = PhoneNumberUtil::UNKNOWN_REGION): bool
    {
        $phoneNumber = $this->getPhoneNumber($phoneNumber);

        if (true === \is_string($type)) {
            $constant = '\libphonenumber\PhoneNumberType::'.$type;

            if (false === \defined($constant)) {
                throw new InvalidArgumentException('The format must be either a constant value or name in libphonenumber\PhoneNumberType');
            }

            $type = \constant('\libphonenumber\PhoneNumberType::'.$type);
        }

        return $this->phoneNumberUtil->getNumberType($phoneNumber) === $type;
    }

    private function getPhoneNumber(PhoneNumber|string $phoneNumber): PhoneNumber
    {
        if (\is_string($phoneNumber)) {
            $phoneNumber = $this->phoneNumberUtil->parse($phoneNumber);
        }

        if (!$phoneNumber instanceof PhoneNumber) {
            throw new InvalidArgumentException('The phone number supplied is not PhoneNumber or string.');
        }

        return $phoneNumber;
    }
}
