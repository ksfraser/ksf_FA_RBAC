<?php
/**
 * CRM Opportunity Status Transition Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMOpportunityStatusTransitionException extends CRMException
{
    private int $opportunityId;
    private string $currentStatus;
    private string $targetStatus;

    public function __construct(int $opportunityId, string $currentStatus, string $targetStatus, array $context = [])
    {
        $message = "Invalid opportunity status transition from '{$currentStatus}' to '{$targetStatus}' for opportunity {$opportunityId}";
        parent::__construct($message, '', $context);
        $this->opportunityId = $opportunityId;
        $this->currentStatus = $currentStatus;
        $this->targetStatus = $targetStatus;
    }

    public function getOpportunityId(): int
    {
        return $this->opportunityId;
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