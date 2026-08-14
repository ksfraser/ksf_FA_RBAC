<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Provisioner;

use Ksfraser\FrontAccounting\Rbac\Contract\DbAdapterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Provisions an FA system user into the CRM person registry and RBAC teams.
 *
 * Called from the FA `authenticate` hook to lazily create:
 *  1. A `crm_persons` row for the user (if none exists)
 *  2. A `crm_contacts` row linking the person to their FA user account (type='user')
 *  3. A `{userId}_individual` team in `rbac_teams`
 *  4. A membership row in `rbac_team_members`
 *
 * All operations are idempotent — re-running for an already-provisioned user
 * is a no-op.
 *
 * @since 1.0.0
 */
class UserProvisioner
{
    /** @var DbAdapterInterface */
    private $db;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param DbAdapterInterface $db
     * @param LoggerInterface    $logger
     *
     * @since 1.0.0
     */
    public function __construct(DbAdapterInterface $db, LoggerInterface $logger = null)
    {
        $this->db     = $db;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Provision an FA user into the person registry and RBAC.
     *
     * Returns the `crm_contacts.id` for the user's 'user' contact record
     * (whether newly created or pre-existing).
     *
     * @param int    $userId  FA numeric user ID (0_users.id)
     * @param string $login   FA login name
     * @param string $name    Display name
     * @param string $email   Email address
     * @return int  crm_contacts.id for the user's 'user' contact record
     *
     * @since 1.0.0
     */
    public function provision(int $userId, string $login, string $name, string $email): int
    {
        $teamId = $userId . '_individual';

        // Step 1 — resolve or create person + contact
        $contactId = $this->resolveContact($userId, $login, $name, $email);

        // Step 2 — resolve or create individual team
        $this->resolveIndividualTeam($teamId, $userId, $login);

        $this->logger->info('UserProvisioner: provisioned user', [
            'userId'    => $userId,
            'login'     => $login,
            'contactId' => $contactId,
            'teamId'    => $teamId,
        ]);

        return $contactId;
    }

    /**
     * Resolve crm_contacts row for this user, creating person + contact if needed.
     *
     * @param int    $userId
     * @param string $login
     * @param string $name
     * @param string $email
     * @return int crm_contacts.id
     *
     * @since 1.0.0
     */
    private function resolveContact(int $userId, string $login, string $name, string $email): int
    {
        $existing = $this->db->fetchAssoc(
            "SELECT id, person_id FROM crm_contacts WHERE type = 'user' AND entity_id = ? LIMIT 1",
            [(string) $userId]
        );

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        // Create crm_persons row. NOTE: stock FA 0_crm_persons has NOT NULL
        // columns without defaults (ref, name, notes) — FA supplies them from
        // the customer/supplier form, so we use the (unique) FA login as the
        // person ref and an empty notes value.
        $this->db->executeUpdate(
            "INSERT INTO crm_persons (ref, name, email, notes, inactive) VALUES (?, ?, ?, '', 0)",
            [$login, $name, $email]
        );
        $personId = $this->db->lastInsertId();

        // Create crm_contacts row linking person → FA user.
        // NOTE: stock FA 0_crm_contacts has NO `inactive` column; identity is
        // resolved via (type, entity_id).
        $this->db->executeUpdate(
            "INSERT INTO crm_contacts (person_id, type, action, entity_id) VALUES (?, 'user', 'general', ?)",
            [$personId, (string) $userId]
        );
        $contactId = $this->db->lastInsertId();

        $this->logger->debug('UserProvisioner: created person + contact', [
            'userId'    => $userId,
            'personId'  => $personId,
            'contactId' => $contactId,
        ]);

        return $contactId;
    }

    /**
     * Resolve the {userId}_individual team, creating it if it does not exist.
     *
     * @param string $teamId
     * @param int    $userId
     * @param string $login
     * @return void
     *
     * @since 1.0.0
     */
    private function resolveIndividualTeam(string $teamId, int $userId, string $login): void
    {
        $existing = $this->db->fetchAssoc(
            "SELECT id FROM rbac_teams WHERE id = ? LIMIT 1",
            [$teamId]
        );

        if ($existing !== null) {
            return;
        }

        // Create the individual team
        $this->db->executeUpdate(
            "INSERT INTO rbac_teams (id, display_name, team_type, owner_id, auto_managed, requires_approval, inactive, created_at, updated_at)
             VALUES (?, ?, 'individual', ?, 1, 0, 0, NOW(), NOW())",
            [$teamId, $login . ' (individual)', (string) $userId]
        );

        // Add the user as owner of their individual team
        $this->db->executeUpdate(
            "INSERT INTO rbac_team_members (team_id, user_id, role, approved, approved_by, added_by, added_at, inactive)
             VALUES (?, ?, 'owner', 1, 'system', 'system', NOW(), 0)",
            [$teamId, (string) $userId]
        );

        $this->logger->debug('UserProvisioner: created individual team', [
            'teamId' => $teamId,
            'userId' => $userId,
        ]);
    }
}
