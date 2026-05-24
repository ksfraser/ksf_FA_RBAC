<?php

namespace Ksfraser\Exceptions\Utility;

/**
 * Exception thrown when validation fails
 *
 * Generic validation exception for any domain. Indicates validation errors such as:
 * - Missing required fields
 * - Invalid data types or formats
 * - Business rule violations
 * - Value range violations
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Utility
 * @since 1.1.0
 */
class ValidationException extends \RuntimeException
{
    /** @var array<int, string> List of validation errors */
    private array $errors;

    /**
     * Create validation exception with error list
     *
     * @param array<int, string> $errors List of validation error messages
     * @param string|null $summary Optional summary message
     */
    public function __construct(array $errors = [], ?string $summary = null)
    {
        $this->errors = array_values($errors); // Re-index
        $message = $summary ?? "Validation failed with " . count($errors) . " error(s)";
        parent::__construct($message);
    }

    /**
     * Get all validation errors
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if specific error exists
     *
     * @param string $errorPattern Pattern to search for
     * @return bool
     */
    public function hasError(string $errorPattern): bool
    {
        foreach ($this->errors as $error) {
            if (stripos($error, $errorPattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get error count
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Create exception from single error message
     *
     * @param string $message The error message
     * @return self
     */
    public static function error(string $message): self
    {
        return new self([$message], $message);
    }

    /**
     * Create exception for missing required field
     *
     * @param array<int, string> $missingFields
     * @return self
     */
    public static function missingFields(array $missingFields): self
    {
        return new self(
            $missingFields,
            'Missing required fields: ' . implode(', ', $missingFields)
        );
    }

    /**
     * Create exception for invalid field values
     *
     * @param array<string, string> $invalidFields Map of field => reason
     * @return self
     */
    public static function invalidFields(array $invalidFields): self
    {
        $errors = [];
        foreach ($invalidFields as $field => $reason) {
            $errors[] = "{$field}: {$reason}";
        }
        return new self($errors, 'Invalid field values');
    }

    /**
     * Create exception for business rule violation
     *
     * @param string $rule The rule that was violated
     * @param string $details Additional details
     * @return self
     */
    public static function ruleViolation(string $rule, string $details = ''): self
    {
        $message = "Business rule violation: {$rule}";
        if ($details) {
            $message .= " ({$details})";
        }
        return new self([$message], $message);
    }
}
