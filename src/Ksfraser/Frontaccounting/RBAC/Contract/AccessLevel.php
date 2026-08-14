<?php

namespace Ksfraser\FrontAccounting\RBAC\Contract;

/**
 * Value Object for access level data returned from RBAC queries.
 *
 * @package Ksfraser\FrontAccounting\RBAC\Contract
 * @since 1.0.0
 */
class AccessLevel implements AccessLevelInterface {

    private $effectiveScope;
    private $viewLevel;
    private $createLevel;
    private $editLevel;
    private $deleteLevel;

    public function __construct(string $effectiveScope, int $viewLevel, int $createLevel, int $editLevel, int $deleteLevel) {
        $this->effectiveScope = $effectiveScope;
        $this->viewLevel = $viewLevel;
        $this->createLevel = $createLevel;
        $this->editLevel = $editLevel;
        $this->deleteLevel = $deleteLevel;
    }

    public function getEffectiveScope(): string {
        return $this->effectiveScope;
    }

    public function getViewLevel(): int {
        return $this->viewLevel;
    }

    public function getCreateLevel(): int {
        return $this->createLevel;
    }

    public function getEditLevel(): int {
        return $this->editLevel;
    }

    public function getDeleteLevel(): int {
        return $this->deleteLevel;
    }

    public function toArray(): array {
        return [
            'effective_scope' => $this->effectiveScope,
            'view_level' => $this->viewLevel,
            'create_level' => $this->createLevel,
            'edit_level' => $this->editLevel,
            'delete_level' => $this->deleteLevel
        ];
    }

}