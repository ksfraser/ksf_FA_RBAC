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
1. Admin navigates to RBAC Access Rules page (`pages/access_matrix.php`)
2. Admin clicks "Add Access Rule" or selects existing access rule
3. System displays current access permissions for the user
4. System shows inheritance chain:
   - Direct team/record grant (highest precedence)
   - Position-inherited grant (from HRM position)
   - Module default (fallback)
5. Admin assigns/removes access levels and scope via checkboxes/dropdowns (None/Mine/Team/All)
6. System validates the assignment
7. System saves the change to `0_rbac_record_access` / `0_rbac_position_permissions`
7. System recalculates effective access levels for all modules and refreshes the session cache
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
- BR-001-Access Control
- FR-001-001-Access_Repository.md
- FR-001-002-Access_Control_UI.md