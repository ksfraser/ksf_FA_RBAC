# FR-001-002-User UI

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide a user interface for managing RBAC role assignments and viewing access matrices. The UI shall display SuiteCRM-style permission grid allowing administrators to configure View/Create/Edit/Delete access with scope options (None/Mine/Team/All).

## Functional Details
1. Display permission matrix grid for selected users with modules listed horizontally and permissions vertically
2. Allow editing of RBAC role assignments with proper inheritance visualization
3. Show current effective RBAC role derived from HRM position hierarchy
4. Provide DDL dropdowns for scope selection: None, Mine, Team, All
5. Support bulk role assignment for position/team changes
6. Display inheritance chain: Employee Role > Position Role > Default Role

## Technical Implementation
- Template: `modules/ksf_FA_RBAC/pages/users.php`
- Helper: `Ksfraser\FA\RBAC\UI\RbacUiHelper`
- Component: `Ksfraser\FA\CRM\UI\RbacGridHelpers`
- API endpoints for role management and matrix rendering

## Linked Use Cases
- UC-001: Add User Use Case
- UC-002: Assign Role to User Use Case
- UC-003: View Access Matrix Use Case