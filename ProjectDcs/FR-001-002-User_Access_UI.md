# FR-001-002-User Access UI

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001-User Access  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide a user interface for managing user access control and viewing access matrices. The UI shall display SuiteCRM-style permission grid allowing administrators to configure View/Create/Edit/Delete access with scope options (None/Mine/Team/All).

## Functional Details
1. Display access matrix grid for selected users with modules listed horizontally and permissions vertically
2. Allow managing of RBAC role assignments with proper inheritance visualization
3. Show current effective RBAC role derived from HRM position hierarchy
4. Provide DDL dropdowns for scope selection: None, Mine, Team, All
5. Support bulk role assignment for position/team changes
6. Display inheritance chain: Direct Role > Position Role > Default Role
7. Show access matrix per user for: View, Create, Edit, Delete permissions

## Technical Implementation
- Template: `modules/ksf_FA_RBAC/pages/user_access.php`
- Helper: `Ksfraser\FA\RBAC\UI\RbacUiHelper`
- Component: `Ksfraser\FA\CRM\UI\RbacGridHelpers`
- API endpoints for role management and matrix rendering
- Integration with RbacService for access calculations

## Linked Use Cases
- UC-001: Add Access Rule
- UC-002: Assign Role to User
- UC-003: View Access Matrix
- UC-004: Revoke Access
- UC-005: Audit Access Changes

## Dependencies
- ksf_FA_HRM module for position/team data
- ksf_RBAC service layer for capability evaluation