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

namespace Misd\PhoneNumberBundle\Form\DataTransformer;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<PhoneNumber, array{country: string, number: string}>
 */
class PhoneNumberToArrayTransformer implements DataTransformerInterface
{
    /**
     * @var string[]
     */
    private array $countryChoices;
    private bool $manageLeadingZeros;

    private PhoneNumberUtil $phoneNumberUtil;

    /**
     * @param string[] $countryChoices
     */
    public function __construct(array $countryChoices, bool $manageLeadingZeros = false)
    {
        $this->countryChoices = $countryChoices;
        $this->manageLeadingZeros = $manageLeadingZeros;
        $this->phoneNumberUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * @return array{country: string, number: string}
     */
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return ['country' => '', 'number' => ''];
        }

        if (!$value instanceof PhoneNumber) {
            throw new TransformationFailedException('Expected a \libphonenumber\PhoneNumber.');
        }

        if (!$value->hasCountryCode() && !$value->hasNationalNumber()) {
            return ['country' => '', 'number' => ''];
        }

        if (false === \in_array($this->phoneNumberUtil->getRegionCodeForNumber($value), $this->countryChoices)) {
            throw new TransformationFailedException('Invalid country.');
        }

        $number = $this->phoneNumberUtil->format($value, PhoneNumberFormat::NATIONAL);

        if ($this->manageLeadingZeros) {
            $number = $this->removeLeadingZeros($number, $value->getNumberOfLeadingZeros());
        }

        return [
            'country' => (string) $this->phoneNumberUtil->getRegionCodeForNumber($value),
            'number' => $number,
        ];
    }

    public function reverseTransform(mixed $value): ?PhoneNumber
    {
        if (!$value) {
            return null;
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        /* @phpstan-ignore-next-line */
        if ('' === trim($value['number'] ?? '')) {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();

        if (preg_match('/\p{L}/u', $value['number'])) {
            throw new TransformationFailedException('The number can not contain letters.');
        }

        try {
            $number = $value['number'];
            if ($this->manageLeadingZeros) {
                $number = $this->addLeadingZeros($number, $value['country']);
            }

            $phoneNumber = $util->parse($number, $value['country']);
        } catch (NumberParseException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if (null !== $phoneNumber && false === \in_array($util->getRegionCodeForNumber($phoneNumber), $this->countryChoices)) {
            throw new TransformationFailedException('Invalid country.');
        }

        return $phoneNumber;
    }

    private function removeLeadingZeros(string $number, int $leadingZeros): string
    {
        if ($leadingZeros <= 0) {
            return $number;
        }

        /* @phpstan-ignore-next-line */
        return preg_replace('/^0{'.$leadingZeros.'}/', '', $number);
    }

    private function addLeadingZeros(string $number, string $country): string
    {
        $metadata = $this->phoneNumberUtil->getMetadataForRegion($country);

        if (!$metadata || !$metadata->getGeneralDesc()?->hasNationalNumberPattern()) {
            return $number;
        }

        $pattern = $metadata->getGeneralDesc()->getNationalNumberPattern();

        // Determine the number of leading zeros to add
        if (!preg_match('/^(0+)/', $pattern, $matches)) {
            return $number;
        }

        $leadingZeros = $matches[1];
        if (str_starts_with($number, $leadingZeros)) {
            return $number;
        }

        return $leadingZeros.$number;
    }
}
