<?php

namespace App\Exceptions;

use RuntimeException;

abstract class DomainRuleException extends RuntimeException
{
    abstract public function errorCode(): string;
}
