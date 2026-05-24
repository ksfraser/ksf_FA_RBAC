<?php
/**
 * CRM Configuration Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMConfigurationException extends CRMException
{
    private string $configKey;

    public function __construct(string $configKey, string $message = '', array $context = [])
    {
        $fullMessage = $message ?: "CRM configuration error for key: {$configKey}";
        parent::__construct($fullMessage, '', $context);
        $this->configKey = $configKey;
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }
}