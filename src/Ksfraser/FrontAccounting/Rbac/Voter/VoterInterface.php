<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Voter;

/**
 * VoterInterface - Contract for RBAC voters.
 *
 * Inspired by Symfony Security Voters.
 * Each voter supports specific actions and subjects.
 *
 * @since 1.0.0
 */
interface VoterInterface
{
    /**
     * Check if this voter supports the given action and subject.
     *
     * @param string $action The action being performed (view, edit, delete, etc.)
     * @param mixed $subject The subject of the action (e.g., customer object, or null for class-level)
     * @return bool True if this voter supports the action/subject combination
     *
     * @since 1.0.0
     */
    public function supports(string $action, $subject): bool;

    /**
     * Vote on the given action and subject.
     *
     * @param string $action The action being performed
     * @param mixed $subject The subject of the action
     * @param array $roles The roles of the user
     * @param array $context Additional context (user_id, module, etc.)
     * @return bool|null True = allow, false = deny, null = abstain
     *
     * @since 1.0.0
     */
    public function voteOnAttribute(string $action, $subject, array $roles, array $context = []): ?bool;
}
