<?php

/**
 * InvalidRepositoryStateException.php
 * 
 * Exception thrown when repository/entity state is invalid
 * 
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Domain
 * @copyright 2025 KS Fraser
 * @license MIT
 * @since 1.0.0
 */

namespace Ksfraser\Exceptions\Domain;

/**
 * Exception for invalid repository or entity state operations
 * 
 * Used for entity creation validation failures and repository state violations
 * 
 * @since 1.0.0
 */
class InvalidRepositoryStateException extends \DomainException
{
    /**
     * When a numeric value is zero but shouldn't be
     * 
     * @param string $field The field name
     * @return self
     * @since 1.0.0
     */
    public static function zeroValueNotAllowed(string $field): self
    {
        return new self(
            "{$field} cannot be zero"
        );
    }

    /**
     * When at least one identifier is required but all are missing
     * 
     * @param string $entity Entity type name
     * @param array $fields Field names that should have at least one value
     * @return self
     * @since 1.0.0
     */
    public static function requiresAtLeastOneIdentifier(string $entity, array $fields): self
    {
        $fieldList = implode(', ', $fields);
        return new self(
            "{$entity} requires at least one identifier: {$fieldList}"
        );
    }

    /**
     * When an operation references the same entity twice
     * 
     * @param string $operation Operation type (e.g., 'transfer', 'match')
     * @param mixed $entityId The entity ID
     * @return self
     * @since 1.0.0
     */
    public static function selfReferencingNotAllowed(string $operation, $entityId): self
    {
        return new self(
            "Cannot {$operation} entity to itself: {$entityId}"
        );
    }

    /**
     * Generic state validation error
     * 
     * @param string $message Custom error message
     * @return self
     * @since 1.0.0
     */
    public static function stateFailed(string $message): self
    {
        return new self($message);
    }
}
