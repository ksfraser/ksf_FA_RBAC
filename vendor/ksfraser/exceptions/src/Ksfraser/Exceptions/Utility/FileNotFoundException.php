<?php

namespace Ksfraser\Exceptions\Utility;

/**
 * Exception thrown when a required file is not found
 *
 * Generic file not found exception usable across any domain that needs
 * to access files (parsers, loaders, repositories, etc).
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Utility
 * @since 1.1.0
 */
class FileNotFoundException extends \RuntimeException
{
    /** @var string The file path that was not found */
    private string $filePath;

    /**
     * Create exception for missing file
     *
     * @param string $filePath The file path that could not be found
     * @param string|null $context Optional context (e.g., "config directory", "import folder")
     * @param ?\Throwable $previous Previous exception for chaining
     */
    public function __construct(
        string $filePath,
        ?string $context = null,
        ?\Throwable $previous = null
    ) {
        $this->filePath = $filePath;
        $message = "File not found: {$filePath}";
        if ($context) {
            $message .= " ({$context})";
        }
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the file path that was not found
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Create exception for missing file
     *
     * @param string $filePath The missing file path
     * @return self
     */
    public static function create(string $filePath): self
    {
        return new self($filePath);
    }

    /**
     * Create exception with context information
     *
     * @param string $filePath The missing file path
     * @param string $context Context description
     * @return self
     */
    public static function withContext(string $filePath, string $context): self
    {
        return new self($filePath, $context);
    }
}
