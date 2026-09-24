<?php

declare(strict_types=1);

namespace Misd\PhoneNumberBundle\Tests\Fixtures\ObjectMapper;

final class SourceMappedContactOutput
{
    public function __construct(
        public readonly string $mobile,
    ) {
    }
}
