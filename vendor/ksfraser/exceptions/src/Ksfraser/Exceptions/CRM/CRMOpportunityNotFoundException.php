<?php
/**
 * CRM Opportunity Not Found Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMOpportunityNotFoundException extends CRMException
{
    private int $opportunityId;

    public function __construct(int $opportunityId, array $context = [])
    {
        parent::__construct("CRM opportunity not found: {$opportunityId}", '', $context);
        $this->opportunityId = $opportunityId;
    }

    public function getOpportunityId(): int
    {
        return $this->opportunityId;
    }
}