<?php declare(strict_types=1);

namespace Composite\Entity\Exceptions;

class EntityException extends \Exception
{
    public function __construct(string $message = "", ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function fromThrowable(\Throwable $throwable): EntityException
    {
        return new EntityException('', $throwable);
    }
}