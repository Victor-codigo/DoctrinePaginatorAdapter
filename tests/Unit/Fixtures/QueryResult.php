<?php

declare(strict_types=1);

namespace VictorCodigo\DoctrinePaginatorAdapter\Tests\Unit\Fixtures;

class QueryResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $age,
    ) {
    }
}
