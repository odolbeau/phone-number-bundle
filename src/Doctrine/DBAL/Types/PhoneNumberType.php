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

namespace Misd\PhoneNumberBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use function gettype;

/**
 * Phone number Doctrine mapping type.
 */
class PhoneNumberType extends Type
{
    /**
     * Phone number type name.
     */
    public const NAME = 'phone_number';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getVarcharTypeDeclarationSQL(['length' => 35]);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof PhoneNumber) {
            throw new ConversionException('Expected \libphonenumber\PhoneNumber, got '. gettype($value));
        }

        return PhoneNumberUtil::getInstance()->format($value, PhoneNumberFormat::E164);
    }

    /**
     * {@inheritdoc}
     *
     * @return PhoneNumber|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?PhoneNumber
    {
        if (null === $value || $value instanceof PhoneNumber) {
            return $value;
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            return $util->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
