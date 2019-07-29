<?php

namespace Vijayd28\LaravelSQS\Exceptions;

use Exception;

/**
 * Class MalformedRequestException
 * @package Vijayd28\LaravelSQS\Exceptions
 */
class MalformedRequestException extends Exception
{
    public function __construct(
        $message = 'Something went wrong in aws job process',
        $code = 500,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
