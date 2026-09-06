# RBAC v2 Architecture Design

**Date:** 2026-09-06
**Status:** Draft
**Module:** ksf_FA_RBAC

---

## 1. Overview

RBAC v2 replaces the v1 `authorize()` hook with a proper voter-based system inspired by Symfony Security and Zend RBAC. It provides:

- **Role/Permission hierarchy** via `zendframework/zend-permissions-rbac`
- **Voter-based authorization** for CRUD operations
- **Module ACL registry** for declarative permissions
- **Dynamic assertions** for record-level access
- **Field-level security** via encrypted columns
- **Event hooks** for dependency-free operation

---

## 2. External Dependencies

### Primary Library: Zend RBAC
```json
{
    "require": {
        "php": ">=7.3",
        "zendframework/zend-permissions-rbac": "^3.2"
    }
}
```

**Why Zend RBAC:**
- Battle-tested (Laminas project, ~15 years old)
- Supports role hierarchy and assertions
- Standalone - no framework dependencies
- MIT license
- PSR-compatible

### Optional: defuse/php-encryption
```json
{
    "require": {
        "php": ">=7.3",
        "defuse/php-encryption": "^2.3"
    }
}
```

---

## 3. Core Components

### 3.1 Role and Permission Model

```
Role
├── name (string) - e.g., 'admin', 'salesman', 'ar_clerk'
├── parents (array) - inherited roles
└── permissions (array)

Permission
├── name (string) - e.g., 'customer.view', 'customer.edit'
└── roles (array) - which roles grant this permission
```

### 3.2 RbacService
```php
namespace ksfraser\FrontAccounting\Rbac;

class RbacService {
    private Rbac $rbac;
    private RoleStorageInterface $roleStorage;
    private PermissionStorageInterface $permStorage;

    public function addRole(string $name, array $parents = []): void;
    public function addPermission(string $permission, array $roles = []): void;
    public function isGranted(string $role, string $permission, AssertionInterface $assertion = null): bool;
    public function getRoles(): array;
    public function getPermissions(): array;
}
```

### 3.3 VoterInterface
```php
namespace ksfraser\FrontAccounting\Rbac;

interface VoterInterface {
    public function supports(string $action, $subject): bool;
    public function voteOnAttribute(string $action, $subject, TokenInterface $token): bool;
}
```

### 3.4 TokenInterface
```php
namespace ksfraser\FrontAccounting\Rbac;

interface TokenInterface {
    public function getUserId(): int;
    public function getRoles(): array;
    public function getUser(): ?object;
}
```

---

## 4. Module ACL Registry

Each module declares its permissions in `hooks.php`:

```php
// hooks.php
function getModuleAcl(&$data, $opts = null) {
    $data['customer'] = [
        'create' => ['admin', 'manager'],
        'view'   => ['admin', 'manager', 'salesman'],
        'edit'   => ['admin', 'manager'],
        'delete' => ['admin'],
        'list'   => ['admin', 'manager', 'salesman', 'clerk'],
        'export' => ['admin', 'manager'],
    ];

    $data['debtor_trans'] = [
        'view'   => ['admin', 'manager', 'ar_clerk', 'salesman'],
        'create' => ['admin', 'manager', 'ar_clerk'],
        'edit'   => ['admin', 'manager', 'ar_clerk'],
        'delete' => ['admin'],
    ];
}
```

---

## 5. Hooks API

### 5.1 Authorization Hook
```php
hook_invoke_all('ksf_FA_RBAC', 'authorize', [
    'user_id'    => $user_id,
    'action'     => 'view',        // create, view, edit, delete, list, export
    'module'     => 'customer',
    'resource'   => $customer_obj, // optional - for record-level
    'assertion'  => function($user, $resource) {
        return $resource->isOwnedBy($user) || $resource->isOnTeam($user);
    }
]);
// Returns: true (allowed), false (denied), null (no opinion/abstain)
```

### 5.2 Filter Record List Hook
```php
hook_invoke_all('ksf_FA_RBAC', 'filterRecordList', [
    'user_id' => $user_id,
    'module'  => 'customer',
    'action'  => 'list',
    'sql'     => "SELECT * FROM debtor_master WHERE 1=1",
    'params'  => [],
]);
// Returns: modified SQL with WHERE clauses applied
```

### 5.3 Filter Fields Hook
```php
hook_invoke_all('ksf_FA_RBAC', 'filterFields', [
    'user_id' => $user_id,
    'module'  => 'customer',
    'fields'  => ['balance', 'credit_limit', 'discount'],
]);
// Returns: array of fields user can access
```

### 5.4 Encrypt Field Hook
```php
hook_invoke_all('ksf_FA_RBAC', 'encryptField', [
    'user_id'  => $user_id,
    'module'   => 'customer',
    'field'    => 'credit_limit',
    'value'    => $plaintext,
    'operation'=> 'encrypt', // or 'decrypt'
]);
// Returns: encrypted/decrypted value with key_id
```

---

## 6. Default Roles

| Role | Description | Inherits |
|------|-------------|----------|
| `admin` | Full system access | - |
| `manager` | Business unit manager | salesman |
| `salesman` | Sales representative | clerk |
| `clerk` | Data entry clerk | - |
| `ar_clerk` | AR data entry | clerk |
| `ap_clerk` | AP data entry | clerk |
| `warehouse` | Warehouse staff | clerk |
| `viewer` | Read-only access | - |

---

## 7. Decision Strategies

### Configurable Strategy
```php
// In config
$decision_strategy = 'affirmative'; // default

// Strategies:
// affirmative: grant if ANY voter grants
// consensus:  grant if MAJORITY grants
// unanimous:  grant if ALL grant
// priority:   first voter to vote wins
```

### Voting Process
```
1. Collect all voters supporting this action/subject
2. If no voters support, abstain
3. Apply decision strategy
4. Return true/false
```

---

## 8. Record-Level Access (Dynamic Assertions)

### Pattern
```php
// When checking access to a specific record
$assertion = function($user_id, $resource) {
    // Check ownership
    if ($resource->getOwnerId() === $user_id) {
        return true;
    }

    // Check team membership
    $user_teams = get_user_teams($user_id);
    $resource_teams = $resource->getTeamIds();
    return !empty(array_intersect($user_teams, $resource_teams));
};

$allowed = $rbac_service->isGranted('salesman', 'customer.view', $assertion);
```

### How Assertions Work
- Injected at check time (not stored in RBAC)
- Closure receives `$user_id` and `$resource`
- Can query any data source (DB, CRM contacts, etc.)
- Allows complex business rules without embedding in RBAC

---

## 9. Field-Level Security

### Sensitive Fields Registry
```php
// In CRM module
$data['sensitive_fields']['debtor_master'] = [
    'discount',
    'credit_limit',
    'payment_terms',
];

$data['sensitive_fields']['sales_orders'] = [
    'customer_ref',
    'internal_note',
];
```

### Encryption Flow
```
Write:
  1. Hook encryptField(field, value, user_id)
  2. Encrypt with user's key
  3. Store: encrypted_value|key_id

Read:
  1. Hook decryptField(field, encrypted_value, user_id)
  2. Look up key_id
  3. Decrypt with appropriate key
```

### Key Management
- User keys: derived from user secret + salt
- Team keys: shared within team
- Company backup key: for recovery
- Key rotation: re-encrypt on key change

---

## 10. Integration with Other Modules

### Without CRM
- Customer→user mapping via native `salesman_code`
- No team-based access
- Record-level checks use `salesman_code` matching

### With CRM
- Customer→team mapping via `crm_company_contacts`
- Team-based access via RBAC
- Complex assertions: owner + team + role

### Without RBAC
- All hooks return `null` (abstain)
- Native FA permissions apply
- No field-level encryption

---

## 11. Implementation Phases

### Phase 1: Core RBAC ✅ COMPLETE
- [x] Integrate `zendframework/zend-permissions-rbac`
- [x] Implement `RbacService` wrapper
- [x] Add role/permission storage
- [x] Implement `authorize` hook
- [x] Decision strategy support

### Phase 2: Voters (In Progress)
- [x] `VoterInterface` definition ✅
- [x] `AbstractVoter` base class ✅
- [ ] Module ACL registry
- [ ] Voter implementations per module
- [x] `filterRecordList` hook ✅

### Phase 3: Record-Level
- [ ] Dynamic assertions
- [ ] CRM integration for customer→team
- [ ] Owner/team checking

### Phase 4: Field Security
- [ ] Sensitive fields registry
- [ ] `defuse/php-encryption` integration
- [ ] `filterFields` hook
- [ ] `encryptField` hook

---

## 12. File Structure

```
ksf_FA_RBAC/
├── composer.json
├── src/
│   ├── RbacService.php
│   ├── Voter/
│   │   ├── VoterInterface.php
│   │   ├── VoterTrait.php
│   │   └── AbstractVoter.php
│   ├── Storage/
│   │   ├── RoleStorageInterface.php
│   │   ├── FaRoleStorage.php
│   │   ├── PermissionStorageInterface.php
│   │   └── FaPermissionStorage.php
│   ├── Token/
│   │   ├── TokenInterface.php
│   │   └── FaUserToken.php
│   ├── Assertion/
│   │   └── AssertionInterface.php
│   └── Exception/
│       └── RbacException.php
├── hooks.php
├── ProjectDcs/
│   └── RBAC_V2_DESIGN.md (this file)
└── sql/
    └── install.sql
```

---

## 13. References

- [Symfony Voters](https://symfony.com/doc/current/security/voters.html)
- [Zend RBAC](https://docs.laminas.dev/laminas-permissions-rbac/)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Apache Shiro](https://shiro.apache.org/permissions.html) (wildcard permissions)

---

*Document version: 1.0.0*
