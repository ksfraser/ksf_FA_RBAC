<?php

/**
 * ConfigurationException.php
 * 
 * Exception thrown for configuration errors
 * 
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Domain
 * @copyright 2025 KS Fraser
 * @license MIT
 * @since 1.0.0
 */

namespace Ksfraser\Exceptions\Domain;

/**
 * Exception for configuration-related errors
 * 
 * Used when configuration loading, parsing, or validation fails
 * 
 * @since 1.0.0
 */
class ConfigurationException extends \RuntimeException
{
    /**
     * When required config key is missing
     * 
     * @param string $key The config key that is missing
     * @param string $context Optional context (e.g., environment, file)
     * @return self
     * @since 1.0.0
     */
    public static function missingKey(string $key, string $context = ''): self
    {
        $message = "Required configuration key missing: {$key}";
        if ($context) {
            $message .= " ({$context})";
        }
        return new self($message);
    }

    /**
     * When config file cannot be loaded
     * 
     * @param string $path The config file path
     * @param string $reason The reason it failed to load
     * @return self
     * @since 1.0.0
     */
    public static function fileNotFound(string $path, string $reason = 'file not found'): self
    {
        return new self(
            "Configuration file error at {$path}: {$reason}"
        );
    }

    /**
     * When config value has invalid type or format
     * 
     * @param string $key The config key
     * @param string $expectedType Expected type
     * @param mixed $actualValue The actual value
     * @return self
     * @since 1.0.0
     */
    public static function invalidType(string $key, string $expectedType, $actualValue): self
    {
        return new self(
            "Configuration {$key} has invalid type: expected {$expectedType}, got " . 
            gettype($actualValue)
        );
    }

    /**
     * Generic configuration error
     * 
     * @param string $message Custom error message
     * @return self
     * @since 1.0.0
     */
    public static function invalid(string $message): self
    {
        return new self($message);
    }
}
