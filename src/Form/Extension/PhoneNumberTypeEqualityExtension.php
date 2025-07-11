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

namespace Misd\PhoneNumberBundle\Form\Extension;

use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PhoneNumberTypeEqualityExtension extends AbstractTypeExtension
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->propertyAccessor = $propertyAccessor;
    }

    public static function getExtendedTypes(): iterable
    {
        return [PhoneNumberType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $newPhoneNumber = $event->getData();

            $parentForm = $event->getForm()->getParent();
            $propertyName = $event->getForm()->getName();

            if (!$parentForm) {
                return;
            }

            $original = $parentForm->getData();
            if (!$original || !$propertyName) {
                return;
            }

            if ($this->propertyAccessor->isReadable($original, $propertyName)) {
                $originalPhoneNumber = $this->propertyAccessor->getValue($original, $propertyName);
            } else {
                trigger_deprecation('odolbeau/phone-number-bundle', '4.2', 'Could not access property "%s" on class "%s". Make sure it is readable or add a getter method.', $propertyName, $original::class);

                return;
            }

            if ($newPhoneNumber instanceof PhoneNumber && $originalPhoneNumber instanceof PhoneNumber && $newPhoneNumber->equals($originalPhoneNumber)) {
                $event->setData($originalPhoneNumber);
            }
        });
    }
}
