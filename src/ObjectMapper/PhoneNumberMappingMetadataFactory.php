<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\ObjectMapper;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;

/**
 * Makes the ObjectMapper convert phone numbers on its own, from the property
 * types: a PhoneNumber mapped to a string property is formatted, a string
 * mapped to a PhoneNumber property is parsed, both with PhoneNumberTransformer.
 * A property that declares its own transform keeps it.
 *
 * It needs Symfony 8.2 or later: before that, the ObjectMapper doesn't tell
 * metadata factories which classes are being mapped, so the types can't be
 * compared and the mapping is left untouched.
 *
 * Symfony flags Mapping as internal, but it is what the metadata factory
 * interface returns: there is no other way to add a mapping.
 */
final class PhoneNumberMappingMetadataFactory implements ObjectMapperMetadataFactoryInterface
{
    /** @var array<string, \ReflectionClass<object>> */
    private array $reflectionClasses = [];

    public function __construct(
        private readonly ObjectMapperMetadataFactoryInterface $inner,
        private readonly PhoneNumberFormat $format = PhoneNumberFormat::E164,
        private readonly string $defaultRegion = PhoneNumberUtil::UNKNOWN_REGION,
    ) {
    }

    public function create(object $object, ?string $property = null, array $context = []): array
    {
        $mappings = $this->inner->create($object, $property, $context);

        if (null === $property || !isset($context['source'], $context['target'])
            || !\is_string($context['source']) || !\is_string($context['target'])
        ) {
            return $mappings;
        }

        [$sourceClass, $targetClass] = [$context['source'], $context['target']];

        if (!$mappings) {
            return $this->convertsPhoneNumber($sourceClass, $property, $targetClass, $property)
                ? [new Mapping(null, null, null, $this->createTransformer())]
                : [];
        }

        foreach ($mappings as $i => $mapping) {
            if (null !== $mapping->transform) {
                continue;
            }

            // The property is the source's or the target's, depending on
            // which side declares the mapping.
            [$sourceProperty, $targetProperty] = $object::class === $sourceClass
                ? [$property, $mapping->target ?? $property]
                : [$mapping->source ?? $property, $property];

            if ($this->convertsPhoneNumber($sourceClass, $sourceProperty, $targetClass, $targetProperty)) {
                $arguments = [$mapping->target, $mapping->source, $mapping->if, $this->createTransformer()];
                // Keeps the mapping restricted to the target class it was declared
                // for. Mapping::$targetClass only exists as of Symfony 8.1, hence
                // the dynamic read.
                $restrictedTo = get_object_vars($mapping)['targetClass'] ?? null;
                if (\is_string($restrictedTo)) {
                    $arguments[] = $restrictedTo;
                }

                $mappings[$i] = new Mapping(...$arguments);
            }
        }

        return $mappings;
    }

    private function convertsPhoneNumber(string $sourceClass, string $sourceProperty, string $targetClass, string $targetProperty): bool
    {
        $sourceType = $this->getPropertyTypeName($sourceClass, $sourceProperty);
        $targetType = $this->getPropertyTypeName($targetClass, $targetProperty);

        if (null === $sourceType || null === $targetType) {
            return false;
        }

        return (is_a($sourceType, PhoneNumber::class, true) && 'string' === $targetType)
            || ('string' === $sourceType && is_a($targetType, PhoneNumber::class, true));
    }

    private function getPropertyTypeName(string $class, string $property): ?string
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflectionClass = $this->reflectionClasses[$class] ??= new \ReflectionClass($class);

        if (!$reflectionClass->hasProperty($property)) {
            return null;
        }

        $type = $reflectionClass->getProperty($property)->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : null;
    }

    private function createTransformer(): PhoneNumberTransformer
    {
        return new PhoneNumberTransformer($this->format, $this->defaultRegion);
    }
}
