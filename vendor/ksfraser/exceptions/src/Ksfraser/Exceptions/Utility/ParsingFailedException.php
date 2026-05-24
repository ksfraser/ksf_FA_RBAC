<?php

namespace Ksfraser\Exceptions\Utility;

/**
 * Exception thrown when parsing file content fails
 *
 * Indicates that file format parsing encountered structural, syntax,
 * or content errors. May include line number context where error occurred.
 *
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Utility
 * @since 1.1.0
 */
class ParsingFailedException extends \RuntimeException
{
    /** @var string The reason for parsing failure */
    private string $reason;

    /** @var int|null Line number where error occurred */
    private ?int $lineNumber;

    /**
     * Create exception for parsing failure
     *
     * @param string $reason The reason for parsing failure
     * @param int|null $lineNumber Optional line number where error occurred
     * @param ?\Throwable $previous Previous exception for chaining
     */
    public function __construct(
        string $reason,
        ?int $lineNumber = null,
        ?\Throwable $previous = null
    ) {
        $this->reason = $reason;
        $this->lineNumber = $lineNumber;
        
        $message = 'Failed to parse file: ' . $reason;
        if ($lineNumber !== null && $lineNumber > 0) {
            $message .= " (line {$lineNumber})";
        }
        
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the reason for parsing failure
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get line number where error occurred
     *
     * @return int|null
     */
    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    /**
     * Create exception for parsing failure
     *
     * @param string $reason The reason for parsing failure
     * @param int $line Optional line number where error occurred
     * @return self
     */
    public static function create(string $reason, int $line = 0): self
    {
        return new self($reason, $line > 0 ? $line : null);
    }

    /**
     * Create exception with detailed context
     *
     * @param string $reason The reason
     * @param int $lineNumber The line number
     * @param string $lineContent The content of the problematic line
     * @return self
     */
    public static function withLineContent(string $reason, int $lineNumber, string $lineContent): self
    {
        $reason = "{$reason} (content: '{$lineContent}')";
        return new self($reason, $lineNumber);
    }
}
