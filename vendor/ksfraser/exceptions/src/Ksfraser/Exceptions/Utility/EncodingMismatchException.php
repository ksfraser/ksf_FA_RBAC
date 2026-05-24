<?php

namespace Ksfraser\Exceptions\Utility;

/**
 * Exception thrown when file encoding does not match expected encoding
 *
 * Indicates that the detected file encoding differs from the expected one,
 * which may cause parsing errors or data corruption.
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Utility
 * @since 1.1.0
 */
class EncodingMismatchException extends \RuntimeException
{
    /** @var string The detected encoding */
    private string $detectedEncoding;

    /** @var string The expected encoding */
    private string $expectedEncoding;

    /**
     * Create exception for encoding mismatch
     *
     * @param string $detectedEncoding The detected file encoding
     * @param string $expectedEncoding The expected file encoding
     * @param ?\Throwable $previous Previous exception for chaining
     */
    public function __construct(
        string $detectedEncoding,
        string $expectedEncoding,
        ?\Throwable $previous = null
    ) {
        $this->detectedEncoding = $detectedEncoding;
        $this->expectedEncoding = $expectedEncoding;
        
        $message = "Encoding mismatch: detected {$detectedEncoding}, expected {$expectedEncoding}";
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the detected encoding
     *
     * @return string
     */
    public function getDetectedEncoding(): string
    {
        return $this->detectedEncoding;
    }

    /**
     * Get the expected encoding
     *
     * @return string
     */
    public function getExpectedEncoding(): string
    {
        return $this->expectedEncoding;
    }

    /**
     * Create exception for encoding mismatch
     *
     * @param string $detected The detected encoding
     * @param string $expected The expected encoding
     * @return self
     */
    public static function create(string $detected, string $expected): self
    {
        return new self($detected, $expected);
    }
}
