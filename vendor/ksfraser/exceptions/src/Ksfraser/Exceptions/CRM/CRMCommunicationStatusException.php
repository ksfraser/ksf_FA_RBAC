<?php
/**
 * CRM Communication Status Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMCommunicationStatusException extends CRMException
{
    private int $communicationId;
    private string $currentStatus;
    private string $targetStatus;

    public function __construct(int $communicationId, string $currentStatus, string $targetStatus, array $context = [])
    {
        $message = "Invalid communication status transition from '{$currentStatus}' to '{$targetStatus}' for communication {$communicationId}";
        parent::__construct($message, '', $context);
        $this->communicationId = $communicationId;
        $this->currentStatus = $currentStatus;
        $this->targetStatus = $targetStatus;
    }

    public function getCommunicationId(): int
    {
        return $this->communicationId;
    }

    public function getCurrentStatus(): string
    {
        return $this->currentStatus;
    }

    public function getTargetStatus(): string
    {
        return $this->targetStatus;
    }
}