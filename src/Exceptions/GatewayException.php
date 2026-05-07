<?php

namespace ConstTech\WhatsappGateway\Exceptions;

use Exception;
use Throwable;

class GatewayException extends Exception
{
    /** @var array<string,mixed> */
    protected $context = [];

    public static function fromResponse(string $message, int $status = 0, array $context = [], ?Throwable $previous = null): self
    {
        $e = new self($message, $status, $previous);
        $e->context = $context;
        return $e;
    }

    /** @return array<string,mixed> */
    public function context(): array
    {
        return $this->context;
    }
}
