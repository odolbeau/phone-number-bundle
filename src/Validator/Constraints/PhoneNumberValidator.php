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

namespace Misd\PhoneNumberBundle\Validator\Constraints;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as PhoneNumberObject;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as PhoneNumberConstraint;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Phone number validator.
 */
class PhoneNumberValidator extends ConstraintValidator
{
    private PhoneNumberUtil $phoneUtil;
    private string $defaultRegion;
    private ?PropertyAccessorInterface $propertyAccessor = null;
    private PhoneNumberFormat $format;

    public function __construct(
        ?PhoneNumberUtil $phoneUtil = null,
        string $defaultRegion = PhoneNumberUtil::UNKNOWN_REGION,
        PhoneNumberFormat|int $format = PhoneNumberFormat::INTERNATIONAL,
    ) {
        if (\is_int($format)) {
            trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Passing an int to the "format" argument is deprecated, pass a libphonenumber\PhoneNumberFormat instance instead.');
            $format = PhoneNumberFormat::from($format);
        }

        $this->phoneUtil = $phoneUtil ?? PhoneNumberUtil::getInstance();
        $this->defaultRegion = mb_strtoupper($defaultRegion);
        $this->format = $format;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PhoneNumberConstraint) {
            return;
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (false === $value instanceof PhoneNumberObject) {
            $value = (string) $value;

            try {
                $phoneNumber = $this->phoneUtil->parse($value, $this->getRegion($constraint));
            } catch (NumberParseException $e) {
                $this->addViolation($value, $constraint);

                return;
            }
        } else {
            $phoneNumber = $value;
            $value = $this->phoneUtil->format($phoneNumber, $constraint->format ?? $this->format);
        }

        if (false === $this->phoneUtil->isValidNumber($phoneNumber)) {
            $this->addViolation($value, $constraint);

            return;
        }

        $validTypes = [];
        foreach ($constraint->getTypes() as $type) {
            switch ($type) {
                case PhoneNumberConstraint::FIXED_LINE:
                    $validTypes[] = PhoneNumberType::FIXED_LINE;
                    $validTypes[] = PhoneNumberType::FIXED_LINE_OR_MOBILE;
                    break;
                case PhoneNumberConstraint::MOBILE:
                    $validTypes[] = PhoneNumberType::MOBILE;
                    $validTypes[] = PhoneNumberType::FIXED_LINE_OR_MOBILE;
                    break;
                case PhoneNumberConstraint::PAGER:
                    $validTypes[] = PhoneNumberType::PAGER;
                    break;
                case PhoneNumberConstraint::PERSONAL_NUMBER:
                    $validTypes[] = PhoneNumberType::PERSONAL_NUMBER;
                    break;
                case PhoneNumberConstraint::PREMIUM_RATE:
                    $validTypes[] = PhoneNumberType::PREMIUM_RATE;
                    break;
                case PhoneNumberConstraint::SHARED_COST:
                    $validTypes[] = PhoneNumberType::SHARED_COST;
                    break;
                case PhoneNumberConstraint::TOLL_FREE:
                    $validTypes[] = PhoneNumberType::TOLL_FREE;
                    break;
                case PhoneNumberConstraint::UAN:
                    $validTypes[] = PhoneNumberType::UAN;
                    break;
                case PhoneNumberConstraint::VOIP:
                    $validTypes[] = PhoneNumberType::VOIP;
                    break;
                case PhoneNumberConstraint::VOICEMAIL:
                    $validTypes[] = PhoneNumberType::VOICEMAIL;
                    break;
            }
        }

        if (0 < \count($validTypes)) {
            $type = $this->phoneUtil->getNumberType($phoneNumber);

            if (!\in_array($type, $validTypes, true)) {
                $this->addViolation($value, $constraint);
            }
        }
    }

    private function getRegion(PhoneNumberConstraint $constraint): string
    {
        $defaultRegion = null;
        if (null !== $path = $constraint->regionPath) {
            $object = $this->context->getObject();
            if (null === $object) {
                throw new \LogicException('The current validation does not concern an object');
            }

            try {
                $defaultRegion = $this->getPropertyAccessor()->getValue($object, $path);
            } catch (NoSuchPropertyException $e) {
                throw new ConstraintDefinitionException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
            }
        }

        return $defaultRegion ?? $constraint->defaultRegion ?? $this->defaultRegion;
    }

    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            if (!class_exists(PropertyAccess::class)) {
                throw new LogicException('Unable to use property path as the Symfony PropertyAccess component is not installed.');
            }
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * Add a violation.
     *
     * @param mixed                 $value      the value that should be validated
     * @param PhoneNumberConstraint $constraint the constraint for the validation
     */
    private function addViolation($value, PhoneNumberConstraint $constraint): void
    {
        $this->context->buildViolation($constraint->getMessage())
            ->setParameter('{{ types }}', implode(', ', $constraint->getTypeNames()))
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setCode(PhoneNumberConstraint::INVALID_PHONE_NUMBER_ERROR)
            ->addViolation();
    }
}
