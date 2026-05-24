<?php
/**
 * CRM Integration Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMIntegrationException extends CRMException
{
    private string $integrationType;
    private string $externalId;

    public function __construct(string $integrationType, string $externalId, string $message, array $context = [])
    {
        $fullMessage = "CRM integration error [{$integrationType}:{$externalId}]: {$message}";
        parent::__construct($fullMessage, '', $context);
        $this->integrationType = $integrationType;
        $this->externalId = $externalId;
    }

    public function getIntegrationType(): string
    {
        return $this->integrationType;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }
}