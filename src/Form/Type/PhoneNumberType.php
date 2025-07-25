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

namespace Misd\PhoneNumberBundle\Form\Type;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\DataTransformer\PhoneNumberToArrayTransformer;
use Misd\PhoneNumberBundle\Form\DataTransformer\PhoneNumberToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Phone number form type.
 */
class PhoneNumberType extends AbstractType
{
    public const WIDGET_SINGLE_TEXT = 'single_text';
    public const WIDGET_COUNTRY_CHOICE = 'country_choice';

    public const DISPLAY_COUNTRY_FULL = 'display_country_full';
    public const DISPLAY_COUNTRY_SHORT = 'display_country_short';

    public const NUMBER_TYPE_TEL = 'tel';
    public const NUMBER_TYPE_TEXT = 'text';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (empty($options['number_type'])) {
            trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Not setting the "number_type" option explicitly is deprecated because its default value will change in version 5.');
            $options['number_type'] = self::NUMBER_TYPE_TEXT;
        }

        $numberType = self::NUMBER_TYPE_TEL === $options['number_type'] ? TelType::class : TextType::class;

        if (self::WIDGET_COUNTRY_CHOICE === $options['widget']) {
            $util = PhoneNumberUtil::getInstance();

            $countries = [];

            if (\is_array($options['country_choices'])) {
                foreach ($options['country_choices'] as $country) {
                    $code = $util->getCountryCodeForRegion($country);

                    if ($code) {
                        $countries[$country] = $code;
                    }
                }
            }

            if (empty($countries)) {
                foreach ($util->getSupportedRegions() as $country) {
                    $countries[$country] = $util->getCountryCodeForRegion($country);
                }
            }

            $countryChoices = [];
            $intlCountries = Countries::getNames();

            foreach ($countries as $regionCode => $countryCode) {
                if (!isset($intlCountries[$regionCode])) {
                    continue;
                }

                $label = $this->formatDisplayChoice(
                    $options['country_display_type'],
                    $intlCountries[$regionCode],
                    (string) $regionCode,
                    (string) $countryCode,
                    $options['country_display_emoji_flag']
                );

                $countryChoices[$label] = $regionCode;
            }

            /**
             * @var string[] $transformerChoices
             */
            $transformerChoices = array_values($countryChoices);

            $countryOptions = array_replace([
                'error_bubbling' => true,
                'disabled' => $options['disabled'],
                'translation_domain' => $options['translation_domain'],
                'choice_translation_domain' => false,
                'required' => true,
                'choices' => $countryChoices,
                'preferred_choices' => $options['preferred_country_choices'],
            ], $options['country_options']);

            if ($options['country_placeholder']) {
                $countryOptions['placeholder'] = $options['country_placeholder'];
            }

            $numberOptions = array_replace([
                'error_bubbling' => true,
                'required' => $options['required'],
                'disabled' => $options['disabled'],
                'translation_domain' => $options['translation_domain'],
            ], $options['number_options']);

            $builder
                ->add('country', ChoiceType::class, $countryOptions)
                ->add('number', $numberType, $numberOptions)
                ->addViewTransformer(new PhoneNumberToArrayTransformer($transformerChoices, $options['manage_leading_zeros']));
        } else {
            $builder->addViewTransformer(
                new PhoneNumberToStringTransformer($options['default_region'], $options['format'])
            );
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['type'] = $options['number_type'] ?? 'tel';
        $view->vars['widget'] = $options['widget'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget' => self::WIDGET_SINGLE_TEXT,
            'compound' => function (Options $options): bool {
                return self::WIDGET_SINGLE_TEXT !== $options['widget'];
            },
            'default_region' => PhoneNumberUtil::UNKNOWN_REGION,
            'format' => PhoneNumberFormat::INTERNATIONAL,
            'invalid_message' => 'This value is not a valid phone number.',
            'by_reference' => false,
            'error_bubbling' => false,
            'country_choices' => [],
            'country_display_type' => self::DISPLAY_COUNTRY_FULL,
            'country_display_emoji_flag' => false,
            'country_placeholder' => false,
            'preferred_country_choices' => [],
            'country_options' => [],
            'number_type' => null,
            'number_options' => [],
            /*
             * Option to manage the addition or removal of leading zeros in phone numbers.
             * This option is only applicable when using the "country_choice" widget.
             * It ensures that leading zeros are handled correctly and avoids duplication
             * when the number is formatted or parsed.
             *
             * @see PhoneNumberToArrayTransformer::addLeadingZeros()
             * @see PhoneNumberToArrayTransformer::removeLeadingZeros()
             */
            'manage_leading_zeros' => false,
        ]);

        $resolver->setAllowedValues('widget', [
            self::WIDGET_SINGLE_TEXT,
            self::WIDGET_COUNTRY_CHOICE,
        ]);

        $resolver->setAllowedTypes('format', ['int', PhoneNumberFormat::class]);
        $resolver->setNormalizer('format', static function ($options, $value) {
            if (\is_int($value)) {
                trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Passing an int to the "format" option is deprecated, pass a libphonenumber\PhoneNumberFormat instance instead.');
            }

            return $value;
        });

        $resolver->setAllowedValues('country_display_type', [
            self::DISPLAY_COUNTRY_FULL,
            self::DISPLAY_COUNTRY_SHORT,
        ]);

        $resolver->setAllowedValues('number_type', [
            null,
            self::NUMBER_TYPE_TEL,
            self::NUMBER_TYPE_TEXT,
        ]);

        $resolver->setAllowedTypes('country_options', 'array');
        $resolver->setAllowedTypes('number_options', 'array');
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'phone_number';
    }

    private function formatDisplayChoice(string $displayType, string $regionName, string $regionCode, string $countryCode, bool $displayEmojiFlag): string
    {
        $formattedDisplay = \sprintf('%s (+%s)', $regionName, $countryCode);
        if (self::DISPLAY_COUNTRY_SHORT === $displayType) {
            $formattedDisplay = \sprintf('%s +%s', $regionCode, $countryCode);
        }

        if ($displayEmojiFlag) {
            $formattedDisplay = self::getEmojiFlag($regionCode).' '.$formattedDisplay;
        }

        return $formattedDisplay;
    }

    private static function getEmojiFlag(string $countryCode): string
    {
        $regionalOffset = 0x1F1A5;

        return mb_chr($regionalOffset + mb_ord($countryCode[0], 'UTF-8'), 'UTF-8')
            .mb_chr($regionalOffset + mb_ord($countryCode[1], 'UTF-8'), 'UTF-8');
    }
}
