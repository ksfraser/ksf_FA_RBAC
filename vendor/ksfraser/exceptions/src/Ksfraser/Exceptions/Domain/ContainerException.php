<?php

/**
 * ContainerException.php
 * 
 * Exception thrown by DI container operations
 * 
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Domain
 * @copyright 2025 KS Fraser
 * @license MIT
 * @since 1.0.0
 */

namespace Ksfraser\Exceptions\Domain;

/**
 * Exception for dependency injection container errors
 * 
 * Used for service registration, resolution, and lifecycle management failures
 * 
 * @since 1.0.0
 */
class ContainerException extends \RuntimeException
{
    /**
     * When a service is not found in the container
     * 
     * @param string $serviceName The service name that was not found
     * @return self
     * @since 1.0.0
     */
    public static function serviceNotFound(string $serviceName): self
    {
        return new self(
            "Service not found in container: {$serviceName}"
        );
    }

    /**
     * When circular dependency is detected
     * 
     * @param array $chain The dependency chain that forms the cycle
     * @return self
     * @since 1.0.0
     */
    public static function circularDependency(array $chain): self
    {
        $chainStr = implode(' → ', $chain);
        return new self(
            "Circular dependency detected: {$chainStr}"
        );
    }

    /**
     * When service resolution fails
     * 
     * @param string $serviceName The service being resolved
     * @param string $reason The reason resolution failed
     * @return self
     * @since 1.0.0
     */
    public static function resolutionFailed(string $serviceName, string $reason): self
    {
        return new self(
            "Failed to resolve service '{$serviceName}': {$reason}"
        );
    }

    /**
     * Generic container error
     * 
     * @param string $message Custom error message
     * @return self
     * @since 1.0.0
     */
    public static function operationFailed(string $message): self
    {
        return new self($message);
    }
}
