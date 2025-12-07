<?php

declare(strict_types=1);

namespace AttributeRouter\Exceptions;

/**
 * RouteNotFoundException
 * 
 * Thrown when no route matches the incoming HTTP request.
 * Returns HTTP 404 status code.
 * 
 * @package AttributeRouter
 * @author Nexus Studio <nexusstudio100@gmail.com>
 */
class RouteNotFoundException extends \Exception
{
    /**
     * Create a new RouteNotFoundException instance
     * 
     * @param string $message Exception message
     * @param int $code HTTP status code (default: 404)
     */
    public function __construct(
        string $message = "Route not found",
        int $code = 404
    ) {
        parent::__construct($message, $code);
    }
}