<?php

namespace App\Exceptions;

final class InsufficientFundsException extends DomainRuleException
{
    public function __construct(
        public readonly string $available,
        public readonly string $requested,
    ) {
        parent::__construct('Insufficient funds.');
    }

    public function errorCode(): string
    {
        return 'insufficient_funds';
    }
}
