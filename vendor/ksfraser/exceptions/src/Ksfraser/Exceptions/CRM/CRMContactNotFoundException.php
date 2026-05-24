<?php
/**
 * CRM Contact Not Found Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMContactNotFoundException extends CRMException
{
    private int $contactId;

    public function __construct(int $contactId, string $debtorNo = '', array $context = [])
    {
        parent::__construct("CRM contact not found: {$contactId}", $debtorNo, $context);
        $this->contactId = $contactId;
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }
}