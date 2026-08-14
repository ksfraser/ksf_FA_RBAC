# FR-001-002-Access Control UI

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001-Access Control  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide a user interface for managing access control and viewing access matrices. The UI shall display a SuiteCRM-style permission grid allowing administrators to configure View/Create/Edit/Delete access with scope options (None/Mine/Team/All).

## Functional Details
1. Display access matrix grid for selected users with modules listed horizontally and permissions vertically
2. Allow managing of RBAC role assignments with proper inheritance visualization
3. Show current effective access level derived from the HRM position hierarchy
4. Provide dropdowns for scope selection: None, Mine, Team, All (library FR-037)
5. Support bulk role assignment for position/team changes
6. Display inheritance chain: Direct > Position > Default (library FR-039)
7. Show access matrix per user for: View, Create, Edit, Delete permissions

## Technical Implementation
- Template: `modules/ksf_FA_RBAC/pages/access_matrix.php` (planned — TODO-AMB-010)
- Helper: `Ksfraser\FrontAccounting\Rbac\UI\RbacUiHelper` (planned)
- API endpoints for role management and matrix rendering
- Integration with the access-level resolver (`AccessLevelResolver`) for access calculations
- Session access-level cache read (library FR-042)

## Linked Use Cases
- UC-001: Add Access Rule

## Dependencies
- ksf_FA_HRM module for position/team data
- `ksfraser/rbac` library service layer for capability and access-level evaluation
