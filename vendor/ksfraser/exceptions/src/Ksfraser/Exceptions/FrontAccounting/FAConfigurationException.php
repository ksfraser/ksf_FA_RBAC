<?php
/**
 * FrontAccounting Configuration Exception
 *
 * @package Ksfraser\Exceptions\FrontAccounting
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\FrontAccounting;

class FAConfigurationException extends FAException
{
    private string $configKey;

    public function __construct(string $configKey, string $message = '', string $moduleName = '', array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        $fullMessage = $message ?: "FrontAccounting configuration error for key: {$configKey}";
        parent::__construct($fullMessage, $moduleName, $context, $code, $previous);
        $this->configKey = $configKey;
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }
}