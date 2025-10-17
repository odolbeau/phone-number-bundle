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

namespace Misd\PhoneNumberBundle\Twig\Extension;

use Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Phone number helper Twig extension.
 */
class PhoneNumberHelperExtension extends AbstractExtension
{
    /**
     * Phone number helper.
     *
     * @var PhoneNumberHelper|null
     *
     * @deprecated Use the PhoneNumberHelper as a Twig Runtime service instead
     */
    protected $helper;

    /**
     * Constructor.
     *
     * @param PhoneNumberHelper|null $helper phone number helper, no longer directly used
     */
    public function __construct(?PhoneNumberHelper $helper = null)
    {
        if (null !== $helper) {
            trigger_deprecation('odolbeau/phone-number-bundle', '4.3', 'Passing the "%s" to "%s" is deprecated in favor of using it as a Twig Runtime service.', PhoneNumberHelper::class, self::class);
        }
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('phone_number_format', [PhoneNumberHelper::class, 'format']),
            new TwigFilter('phone_number_format_out_of_country_calling_number', [PhoneNumberHelper::class, 'formatOutOfCountryCallingNumber']),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('phone_number_of_type', [PhoneNumberHelper::class, 'isType']),
        ];
    }

    /**
     * @return string
     *
     * @deprecated Unused by Twig since 2.0
     */
    public function getName()
    {
        return 'phone_number_helper';
    }
}
