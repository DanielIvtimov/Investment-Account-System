<?php

namespace App\Exceptions;

final class InsufficientHoldingsException extends DomainRuleException
{
    public function __construct(
        public readonly string $ticker,
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct("Insufficient holdings for {$ticker}.");
    }

    public function errorCode(): string
    {
        return 'insufficient_holdings';
    }
}