# AGENTS.md — ksf_FA_RBAC

## Architecture Overview

FrontAccounting adapter for `ksfraser/rbac` (business logic). Bridges the RBAC service to FA's database layer, session system, and user authentication hooks.

### Core Principles

- **SOLID**, **DRY**, **TDD**, **DI**, **SRP**
- PHP 7.4 hard constraint — no PHP 8+ syntax
- Zero FA coupling in `ksfraser/rbac` library; all FA integration lives here

## Repository Structure

```
ksf_FA_RBAC/
├── src/Ksfraser/FrontAccounting/Rbac/
│   ├── Contract/
│   │   └── DbAdapterInterface.php       — minimal DB abstraction
│   ├── Adapter/
│   │   └── FaDbAdapter.php              — FA db_query() wrapper
│   ├── Repository/
│   │   ├── FaTeamRepository.php         — TeamRepositoryInterface impl
│   │   └── FaRecordAccessRepository.php — RecordAccessRepositoryInterface impl
│   └── Provisioner/
│       └── UserProvisioner.php          — lazy provision users on authenticate hook
├── tests/Unit/
│   ├── Repository/
│   │   ├── FaTeamRepositoryTest.php
│   │   └── FaRecordAccessRepositoryTest.php
│   └── Provisioner/
│       └── UserProvisionerTest.php
├── sql/
│   └── install.sql                      — 4 RBAC tables + 'user' crm_category seed
├── hooks.php                            — module registration + authenticate hook
├── composer.json
└── phpunit.xml
```

## Database Tables

### 0_rbac_teams
- `id` (VARCHAR 64) — team identifier (e.g. `'5_individual'` for user 5's personal team)
- `display_name` — human-readable name
- `team_type` — `'individual'` | `'explicit'` | `'org_direct'` | `'org_indirect'` | `'service_account'`
- `owner_id` — FK to `0_users.id` (or synthetic for service accounts)
- `auto_managed` — `1` for system-managed (individual teams, org chart auto-teams)
- `requires_approval` — `1` if membership requires approval
- `inactive` — `1` for soft-delete

### 0_rbac_team_members
- `id` (INT AUTO) — primary key
- `team_id` — FK to `0_rbac_teams.id`
- `user_id` — FK to `0_users.id`
- `role` — `'member'` | `'owner'`
- `approved` — `1` when approved; `0` pending
- `added_by`, `approved_by`, `removed_by` — user IDs
- `inactive` — soft-delete

### 0_rbac_record_access
- `id` (INT AUTO) — xref row primary key
- `module`, `record_type`, `record_id` — what is being accessed
- `team_id` — who has access (FK to `0_rbac_teams.id`)
- `projection` — DTO projection name (e.g. `'public'`, `'account'`, `'full'`)
- `can_view`, `can_edit`, `can_delete`, `can_export`, `can_print`, `can_invite`, `can_restore` — capability flags
- `granted_by`, `granted_at`, `expires_at` — grant metadata
- `inactive` — soft-delete

### 0_rbac_audit_log
- `id` (INT AUTO) — append-only log entry
- `action` — `'grant'` | `'revoke'` | `'elevate'` | `'role_assign'` | `'role_revoke'` | `'provision'`
- `actor_id` — user performing the action
- `target_id` — affected user or team
- `module`, `record_type`, `record_id` — context
- `details` — JSON payload
- `ip_address` — source IP

## Key Implementation Details

### DbAdapterInterface

Minimal interface abstracting FA's `db_query()` API:
- `fetchAssoc(string $sql, array $params): ?array`
- `fetchAll(string $sql, array $params): array`
- `executeUpdate(string $sql, array $params): int`
- `lastInsertId(): int`

FaDbAdapter implements this, handling:
- Table name prefixing (TB_PREF → `0_`)
- ? placeholder substitution with `mysqli_real_escape_string()`

### FaTeamRepository

Implements `Ksfraser\Rbac\Contract\TeamRepositoryInterface`.

Key methods:
- `findById(string $teamId): ?Team`
- `save(Team $team): void` — uses ON DUPLICATE KEY UPDATE for idempotence
- `findDirectTeamIdsForUser(string $userId): string[]`
- `findEffectiveTeamIdsForUser(string $userId): string[]` (same as direct for now; TODO recursive CTE)
- `addMember(TeamMember $member): void`
- `approveMember(string $teamId, string $userId, string $approvedBy): void`
- `removeMember(string $teamId, string $userId, string $removedBy): void` — sets inactive
- `exists(string $teamId): bool`
- `deactivate(string $teamId): void`

### FaRecordAccessRepository

Implements `Ksfraser\Rbac\Contract\RecordAccessRepositoryInterface`.

Key methods:
- `findForRecord(string $module, string $recordType, int $recordId, array $teamIds): RecordAccess[]`
- `save(RecordAccess $access): void`
- `deactivateForTeam(string $module, string $recordType, int $recordId, string $teamId): void`
- `reassign(string $fromTeamId, string $toTeamId, string $reassignedBy, array $recordIds, ...): int`
- `buildAccessJoinSql(string $module, string $recordType, string $tableAlias): string` — returns SQL JOIN fragment for default-deny enforcement

### UserProvisioner

Called from FA's `authenticate` hook (in hooks.php) to lazily create:

1. `crm_persons` row (if not exists)
2. `crm_contacts` row with `type='user'` linking to `0_users.id` (if not exists)
3. `{userId}_individual` team (if not exists)
4. Team membership row (if not exists)

All operations are idempotent.

## Testing

```bash
cd ksf_FA_RBAC
composer install --dev
vendor/bin/phpunit
```

All tests are unit-level (no FA DB required). Database interactions are mocked via DbAdapterInterface.

---

## TODO (Ambiguities & Future Work)

- **TODO-AMB-010**: Users-to-Contacts bulk provisioning UI page (RBAC Setup menu)
- **TODO-AMB-011**: ContactTypeRegistry extensibility (how ksf_FA_HRM, ksf_FA_CRM, etc. register types)
- **Recursive team nesting** (CTE expansion in findEffectiveTeamIdsForUser)
- **Role definitions** (RoleRepositoryInterface implementation)
