# UC-001-Add Access Rule

**Module**: ksf_FA_RBAC  
**Priority**: High  
**Status**: Draft  

## Actor
System Administrator

## Preconditions
- User has SA_RBAC_ADMIN security area
- HRM employee record exists for the user
- RBAC module is active and configured

## Main Flow
1. Admin navigates to RBAC Access Rules page (`pages/user_access.php`)
2. Admin clicks "Add Access Rule" or selects existing access rule
3. System displays current access permissions for the user
4. System shows inheritance chain:
   - Direct RBAC role (highest precedence)
   - Position-inherited RBAC role (from HRM position)
   - Default role (fallback)
5. Admin assigns/removes RBAC permissions via checkboxes
6. System validates permission assignment
7. System saves permission changes to `rbac_role_matrix` table
7. System recalculates effective permissions for all modules
8. System displays updated access matrix

## Postconditions
- User's access permissions have been updated
- Access matrix reflects new effective permissions
- Audit log records the permission change

## Alternative Flows
**A1: Permission conflict detected**
- System warns about conflicting assignments
- Admin must resolve conflicts before saving

**A2: Bulk permission changes via position update**
- Admin changes user's position in HRM
- System automatically updates inherited RBAC permissions
- Direct assigned permissions remain unchanged

**A3: Permission inheritance review**
- Admin reviews current inherited permissions
- Admin can override by assigning specific permissions manually

## Linked Requirements
- BR-001-User Access
- FR-001-001-User_Access_Repository.md
- FR-001-002-User_Access_UI.md