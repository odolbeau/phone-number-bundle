<?php

namespace Misd\PhoneNumberBundle\Twig;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigTest;

class PhoneNumberHelperExtension
{
    protected PhoneNumberHelper $helper;

    public function __construct(PhoneNumberUtil $phoneNumberUtil)
    {
        $this->helper = new PhoneNumberHelper($phoneNumberUtil);
    }

    #[AsTwigTest('phone_number_of_type')]
    public function phoneNumberOfType($phoneNumber, string $type = PhoneNumberUtil::UNKNOWN_REGION): bool
    {
        return $this->helper->isType($phoneNumber, $type);
    }

    #[AsTwigFilter('phone_number_format')]
    public function phoneNumberFormat($phoneNumber, string|int|PhoneNumberFormat|null $format = null, ?string $region = null): string
    {
        return $this->helper->format($phoneNumber, $format, $region);
    }
}
