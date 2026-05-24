<?php
/**
 * FrontAccounting Entity Not Found Exception
 *
 * @package Ksfraser\Exceptions\FrontAccounting
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\FrontAccounting;

use Ksfraser\Exceptions\Domain\EntityNotFoundException;

class FAEntityNotFoundException extends FAException
{
    private string $entityType;
    private string $entityId;

    public function __construct(string $entityType, string $entityId, string $moduleName = '', array $context = [])
    {
        parent::__construct("{$entityType} not found with id: {$entityId}", $moduleName, $context);
        $this->entityType = $entityType;
        $this->entityId = $entityId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }
}