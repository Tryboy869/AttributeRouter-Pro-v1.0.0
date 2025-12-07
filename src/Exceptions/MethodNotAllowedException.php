<?php

declare(strict_types=1);

namespace AttributeRouter\Exceptions;

/**
 * MethodNotAllowedException
 * 
 * Thrown when a route exists but the HTTP method is not allowed.
 * For example: POST request to a route that only accepts GET.
 * Returns HTTP 405 status code.
 * 
 * @package AttributeRouter
 * @author Nexus Studio <nexusstudio100@gmail.com>
 */
class MethodNotAllowedException extends \Exception
{
    /**
     * Create a new MethodNotAllowedException instance
     * 
     * @param string $message Exception message
     * @param int $code HTTP status code (default: 405)
     */
    public function __construct(
        string $message = "Method not allowed",
        int $code = 405
    ) {
        parent::__construct($message, $code);
    }
}