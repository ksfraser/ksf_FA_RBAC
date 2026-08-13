# BR-001-User Management

**Module**: ksf_FA_RBAC  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide user management capabilities including creation, modification, and deactivation of user accounts with role-based access control (RBAC). Users shall be assigned through HRM positions/teams or direct RBAC role assignments. Access to CRM and other modules shall be governed by the SuiteCRM-style permission matrix grid.

## Business Rules
1. Users can be assigned RBAC roles directly or through HRM position mappings
2. Employee RBAC roles override position-inherited roles
3. Position roles take precedence over default HRM roles
4. Access matrix grid provides View/Create/Edit/Delete permissions across modules with scope selection (None/Mine/Team/All)
5. Deactivated users lose all RBAC access immediately

## Linked Functional Requirements
- FR-001-001: Add User Repository
- FR-001-002: Add User UI
- UC-001: Add User Use Case