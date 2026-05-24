<?php

namespace Ksfraser\Exceptions\Utility;

/**
 * Exception thrown when file type is not supported by parser
 *
 * Indicates that the file type (MIME type or extension) is not
 * in the list of supported formats.
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Utility
 * @since 1.1.0
 */
class UnsupportedFileTypeException extends \RuntimeException
{
    /** @var string The unsupported file type */
    private string $fileType;

    /** @var array<string> List of supported types */
    private array $supportedTypes;

    /**
     * Create exception for unsupported file type
     *
     * @param string $fileType The unsupported MIME type or file extension
     * @param array<string> $supportedTypes List of types that are supported
     * @param ?\Throwable $previous Previous exception for chaining
     */
    public function __construct(
        string $fileType,
        array $supportedTypes = [],
        ?\Throwable $previous = null
    ) {
        $this->fileType = $fileType;
        $this->supportedTypes = $supportedTypes;
        
        $message = sprintf(
            'Unsupported file type: %s. Supported types: %s',
            $fileType,
            implode(', ', $supportedTypes ?: ['(none specified)'])
        );
        
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the unsupported file type
     *
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * Get list of supported types
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     * Create exception for unsupported file type
     *
     * @param string $fileType The unsupported MIME type or extension
     * @param array<string> $supported List of supported types
     * @return self
     */
    public static function create(string $fileType, array $supported): self
    {
        return new self($fileType, $supported);
    }
}
