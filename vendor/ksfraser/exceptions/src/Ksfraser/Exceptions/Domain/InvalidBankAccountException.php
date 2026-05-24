<?php

/**
 * InvalidBankAccountException.php
 * 
 * Exception thrown when bank account validation fails, such as:
 * - FROM and TO accounts are the same (self-transfers)
 * - Account not found
 * - Insufficient funds
 * - Invalid account configuration or status
 * 
 * @author KS Fraser
 * @package Ksfraser\Exceptions\Domain
 * @copyright 2025 KS Fraser
 * @license MIT
 * @since 1.0.0
 */

namespace Ksfraser\Exceptions\Domain;

/**
 * Exception for invalid bank account operations
 * 
 * Provides static factory methods for creating specific account validation errors,
 * following Domain-Driven Design exception patterns.
 * 
 * Example usage:
 * <code>
 * if ($fromAccount == $toAccount) {
 *     throw InvalidBankAccountException::fromAndToAccountsAreSame($fromAccount);
 * }
 * 
 * try {
 *     // bank transfer processing
 * } catch (InvalidBankAccountException $e) {
 *     display_error(_($e->getMessage()));
 *     return $this->createErrorResult($e->getMessage());
 * }
 * </code>
 * 
 * @since 1.0.0
 */
class InvalidBankAccountException extends \InvalidArgumentException
{
    /**
     * When FROM and TO accounts are the same in a bank transfer (self-transfer)
     * 
     * @param int $accountId The account ID that is both FROM and TO
     * 
     * @return self New exception instance
     * 
     * @since 1.0.0
     */
    public static function fromAndToAccountsAreSame(int $accountId): self
    {
        return new self(
            "To and From accounts must not be the same account (account {$accountId})"
        );
    }

    /**
     * When a bank account is not found in the system
     * 
     * @param int $accountId The account ID that was not found
     * 
     * @return self New exception instance
     * 
     * @since 1.0.0
     */
    public static function notFound(int $accountId): self
    {
        return new self(
            "Bank account not found: {$accountId}"
        );
    }

    /**
     * When insufficient funds available for a transfer
     * 
     * @param float $required Required transfer amount
     * @param float $available Available balance
     * 
     * @return self New exception instance
     * 
     * @since 1.0.0
     */
    public static function insufficientFunds(float $required, float $available): self
    {
        return new self(
            "Insufficient funds: required {$required}, available {$available}"
        );
    }

    /**
     * When account is inactive, disabled, or not available for transfers
     * 
     * @param int $accountId The account ID
     * @param string $reason The reason the account is invalid (inactive, disabled, suspended, etc)
     * 
     * @return self New exception instance
     * 
     * @since 1.0.0
     */
    public static function inactive(int $accountId, string $reason = 'inactive'): self
    {
        return new self(
            "Bank account {$accountId} is {$reason}"
        );
    }

    /**
     * When an account currency is not supported or mismatched
     * 
     * @param int $accountId The account ID
     * @param string $currency The currency code
     * @param string $reason Explanation (unsupported, mismatch, etc)
     * 
     * @return self New exception instance
     * 
     * @since 1.0.0
     */
    public static function invalidCurrency(int $accountId, string $currency, string $reason = 'unsupported'): self
    {
        return new self(
            "Bank account {$accountId} has {$reason} currency: {$currency}"
        );
    }

    /**
     * When account access is restricted or denied
     * 
     * @param int $accountId The account ID
     * @param string $reason Explanation (restricted, denied, etc)
     * 
     * @return self New exception instance
     * 
     * @since 1.0.0
     */
    public static function accessDenied(int $accountId, string $reason = 'access denied'): self
    {
        return new self(
            "Cannot access bank account {$accountId}: {$reason}"
        );
    }
}
