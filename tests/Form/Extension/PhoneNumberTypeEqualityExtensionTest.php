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

namespace Misd\PhoneNumberBundle\Tests\Form\Extension;

use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Form\Extension\PhoneNumberTypeEqualityExtension;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PhoneNumberTypeEqualityExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return [
            new PhoneNumberTypeEqualityExtension($accessor),
        ];
    }

    public function testNoChangeKeepsOriginalInstance(): void
    {
        $phone = new PhoneNumber();
        $phone->setCountryCode(33);
        $phone->setNationalNumber('612345678');

        $entity = new class($phone) {
            private PhoneNumber $phoneNumber;

            public function __construct(PhoneNumber $phoneNumber)
            {
                $this->phoneNumber = $phoneNumber;
            }

            public function getPhoneNumber(): PhoneNumber
            {
                return $this->phoneNumber;
            }

            public function setPhoneNumber(PhoneNumber $phoneNumber): void
            {
                $this->phoneNumber = $phoneNumber;
            }
        };

        $form = $this->factory->createBuilder(FormType::class, $entity)
            ->add('phoneNumber', PhoneNumberType::class, [
                'number_type' => PhoneNumberType::NUMBER_TYPE_TEL,
            ])
            ->getForm();

        $form->submit(['phoneNumber' => '+33612345678']);

        $this->assertSame($entity->getPhoneNumber(), $phone);
    }
}
