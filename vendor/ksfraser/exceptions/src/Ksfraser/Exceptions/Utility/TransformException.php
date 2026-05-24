<?php

namespace Ksfraser\Exceptions\Utility;

/**
 * Exception thrown when transformation from one format to another fails
 *
 * Generic transformation exception for DTO-to-Entity, object mapping, or any
 * data transformation. Indicates errors such as:
 * - Cannot create target object/entity with invalid data
 * - Missing required properties
 * - Type mismatch during conversion
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Utility
 * @since 1.1.0
 */
class TransformException extends \RuntimeException
{
    /**
     * Create exception for entity/object creation failure
     *
     * @param string $targetType The target type that failed to create
     * @param string $reason The reason for failure
     * @return self
     */
    public static function entityCreationFailed(string $targetType, string $reason): self
    {
        return new self(
            "Failed to create {$targetType}: {$reason}"
        );
    }

    /**
     * Create exception for type mismatch
     *
     * @param string $field The field with type mismatch
     * @param string $expectedType The expected type
     * @param string $actualType The actual type provided
     * @return self
     */
    public static function typeMismatch(string $field, string $expectedType, string $actualType): self
    {
        return new self(
            "Type mismatch for field '{$field}': expected {$expectedType}, got {$actualType}"
        );
    }

    /**
     * Create exception for missing required data
     *
     * @param array<int, string> $missingFields The missing required fields
     * @return self
     */
    public static function missingRequiredData(array $missingFields): self
    {
        return new self(
            "Missing required data for transformation: " . implode(', ', $missingFields)
        );
    }
}
