<?php
/**
 * CRM Communication Not Found Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMCommunicationNotFoundException extends CRMException
{
    private int $communicationId;

    public function __construct(int $communicationId, array $context = [])
    {
        parent::__construct("CRM communication not found: {$communicationId}", '', $context);
        $this->communicationId = $communicationId;
    }

    public function getCommunicationId(): int
    {
        return $this->communicationId;
    }
}