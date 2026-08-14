# BR-001-Access Control

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide role based access control for records across all KSF modules (CRM, Calendar, HRM, etc.). Users shall be granted View/Create/Edit/Delete access levels based on their roles, positions, and team memberships. Access to records shall be governed by a role-based access matrix that considers:
- Direct team/record grants (highest precedence)
- Position/role-inherited grants (from HRM positions)
- Module default access levels (fallback)

The module answers *"what access level for entity (team/role/person/...)"* to calling modules via the hook/DTO contract (library FR-038). Access levels follow the SuiteCRM-style scopes: **None / Mine / Team / All** (library FR-037).

## Functional Details
1. The system shall provide an access matrix grid for each user showing View/Create/Edit/Delete access levels across modules
2. Users shall be able to see which records they can access based on their assigned roles
3. Access matrix shall reflect the inheritance hierarchy: Direct > Position > Default (library FR-039)
4. The system shall enforce access controls at the record level
5. The system shall resolve the access level for each active module at login and cache it in the session (library FR-042)
6. A **deactivated user shall lose all RBAC access immediately** — every access-level query and capability check treats a deactivated user as having no effective teams and no grants (see FR-001-003)

## Linked Requirements
- BR-001: Role Based Access Control
- FR-001-001-Access_Repository.md
- FR-001-002-Access_Control_UI.md
- FR-001-003-Deactivated_Users_Lose_Access.md

## Technical Implementation
- Adapter classes: `Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter`
- Repositories: `Ksfraser\FrontAccounting\Rbac\Repository\FaTeamRepository`, `FaRecordAccessRepository`
- Planned access-level resolver: `Ksfraser\FrontAccounting\Rbac\Access\AccessLevelResolver` (implements library FR-037–FR-042)
- Database tables: `0_rbac_teams`, `0_rbac_team_members`, `0_rbac_record_access`, `0_rbac_audit_log` (plus planned `0_rbac_module_defaults`, `0_rbac_position_permissions` per library Arch §11.4)
- Inheritance logic: Direct > Position > Default

## Linked Use Case
- UC-001: Add Access Rule

## Notes
- This module does NOT handle user creation/management (that belongs to the User Management module)
- Focus is solely on access control and permission enforcement
- Works with the HRM module to map positions to roles
- Integrates with the `ksfraser/rbac` library for capability and access-level evaluation
