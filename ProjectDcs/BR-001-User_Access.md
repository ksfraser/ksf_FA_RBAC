# BR-001-User_Access

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide access control mechanisms for CRM records. Users shall be granted View/Create/Edit/Delete permissions based on their roles and positions. Access to records shall be governed by a role-based access matrix that considers:
- Direct employee RBAC roles (highest precedence)
- Position-inherited RBAC roles (from HRM positions)
- Default roles (fallback)

## Functional Details
1. The system shall provide an access matrix grid for each user showing View/Create/Edit/Delete permissions across modules
2. Users shall be able to see which records they can access based on their assigned roles
3. Access matrix shall reflect the inheritance hierarchy: Direct Role > Position Role > Default Role
4. The system shall enforce access controls at the record level

## Linked Requirements
- BR-001: User Access Management
- FR-001-001-User_Access_Repository.md
- FR-001-002-User_Access_UI.md
- FR-001-003-Access_Control_UI.md

## Technical Implementation
- Interface: `Ksfraser\RBAC\Repository\UserAccessRepositoryInterface`
- Implementation: `Ksfraser\RBAC\Service\AccessService.php`
- Database tables: `hrm_employee_roles`, `hrm_position_roles`, `rbac_role_matrix`
- Inheritance logic: Direct Role > Position Role > Default Role

## Linked Use Case
- UC-001: Add Access Rule
- UC-002: Assign Role to User
- UC-003: View Access Matrix
- UC-004: Revoke Access
- UC-005: Audit Access Changes

## Notes
- This module does NOT handle user creation/management (that belongs to User Management module)
- Focus is solely on access control and permission enforcement
- Works with the HRM module to map positions to roles
- Integrates with RBAC service for capability evaluation
