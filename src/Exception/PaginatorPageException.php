<?php

declare(strict_types=1);

namespace VictorCodigo\DoctrinePaginatorAdapter\Exception;

class PaginatorPageException extends \InvalidArgumentException
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
