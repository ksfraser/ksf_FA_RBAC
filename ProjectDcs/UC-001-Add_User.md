# UC-001-Add_User

**Module**: ksf_FA_RBAC  
**Priority**: High  
**Status**: Draft  

## Actor
System Administrator / HR Manager

## Preconditions
- User has SA_RBAC_ADMIN security area
- HRM employee record exists for the user
- RBAC module is active and configured

## Main Flow
1. Admin navigates to RBAC Users page (`pages/users.php`)
2. Admin clicks "Add User" or selects existing user
3. System displays user details with current effective RBAC role
4. System shows inheritance chain:
   - Direct RBAC role (if assigned)
   - Position-inherited role (from HRM position)
   - Default role (fallback)
5. Admin assigns or changes direct RBAC role via dropdown
6. System validates role assignment
7. System saves assignment to `rbac_employee_roles` table
7. System recalculates effective capabilities for all modules
8. System displays updated access matrix

## Postconditions
- User has updated RBAC role assignment
- Access matrix reflects new effective permissions
- Audit log records the role change

## Alternative Flows
**A1: User has no HRM position**
- System shows only direct RBAC role assignment option
- No inheritance chain displayed

**A2: Position role conflicts with direct role**
- System warns about override behavior
- Direct role takes precedence

**A3: Bulk assignment via position change**
- Admin changes user's position in HRM
- System automatically updates inherited RBAC role
- Direct employee role remains unchanged if set

## Linked Requirements
- BR-001: User Management
- FR-001-001: User Repository
- FR-001-002: User UI