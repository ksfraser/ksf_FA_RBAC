# ksf_FA_RBAC

FrontAccounting adapter for the **ksfraser/rbac** framework-agnostic RBAC library.

Provides:
- **User provisioning** — lazy creation of FA users in the CRM person registry on authenticate hook
- **Database repositories** — concrete implementations of RBAC interfaces against FA tables
- **SQL JOIN fragments** — enforces default-deny visibility at the query layer
- **Soft-delete + audit logging** — all record mutations are logged immutably

## Installation

1. Copy this directory to `/path/to/fa/modules/ksf_FA_RBAC`
2. In FA Setup → Extensions, activate **KSF RBAC**
3. Composer dependencies are installed on first activation

## Quick Start

### Provisioning a User

When a user authenticates, the `authenticate` hook automatically:
1. Creates a `crm_persons` row (if needed)
2. Creates a `crm_contacts` row linking the person to their FA user account (`type='user'`, `entity_id=user_id`)
3. Creates a `{userId}_individual` team
4. Adds the user as a member of their individual team

No manual action required.

### Enforcing Visibility

Use `buildAccessJoinSql()` in any record-fetching query to enforce RBAC:

```php
$db = new FaDbAdapter(TB_PREF);
$repo = new FaRecordAccessRepository($db);

$joinFragment = $repo->buildAccessJoinSql('calendar', 'entry', 'e');

$sql = "SELECT e.* FROM fa_cal_entries e"
     . " $joinFragment"
     . " WHERE e.start_date BETWEEN ? AND ?"
     . " ORDER BY e.start_date ASC";

$entries = $db->fetchAll($sql, [$userId, $start, $end]);
```

The JOIN fragment:
- Restricts to teams the user is a member of
- Enforces `inactive=0` and expiry checks
- Returns empty result set if user has no access (default deny)

## Architecture

```
ksfraser/rbac (library)
    ↓
ksf_FA_RBAC (FA adapter) ← you are here
    ├─ FaDbAdapter
    ├─ FaTeamRepository
    ├─ FaRecordAccessRepository
    └─ UserProvisioner
```

All repositories implement interfaces from `ksfraser/rbac`, ensuring zero coupling to FA in the library.

## Testing

```bash
cd /path/to/ksf_FA_RBAC
composer install --dev
vendor/bin/phpunit
```

## Documentation

See `AGENTS.md` for detailed architecture, database schema, and implementation notes.
