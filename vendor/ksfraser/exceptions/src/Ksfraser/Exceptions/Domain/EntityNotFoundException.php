<?php

/**
 * EntityNotFoundException.php
 * 
 * Exception thrown when an entity cannot be found
 * 
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Domain
 * @copyright 2025 KS Fraser
 * @license MIT
 * @since 1.0.0
 */

namespace Ksfraser\Exceptions\Domain;

/**
 * Exception for entity not found scenarios
 * 
 * Used by repository pattern when requested entities don't exist
 * 
 * @since 1.0.0
 */
class EntityNotFoundException extends \RuntimeException
{
    /**
     * When entity with given ID is not found
     * 
     * @param string $entityType Entity class or type name
     * @param mixed $id The ID that was searched for
     * @return self
     * @since 1.0.0
     */
    public static function withId(string $entityType, $id): self
    {
        return new self(
            "{$entityType} not found with id: {$id}"
        );
    }

    /**
     * When entity with given criteria is not found
     * 
     * @param string $entityType Entity class or type name
     * @param array $criteria Search criteria
     * @return self
     * @since 1.0.0
     */
    public static function withCriteria(string $entityType, array $criteria): self
    {
        $criteriaStr = http_build_query($criteria);
        return new self(
            "{$entityType} not found matching: {$criteriaStr}"
        );
    }

    /**
     * Generic not found error
     * 
     * @param string $message Custom error message
     * @return self
     * @since 1.0.0
     */
    public static function notFound(string $message): self
    {
        return new self($message);
    }
}
