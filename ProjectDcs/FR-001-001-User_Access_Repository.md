# FR-001-001-User Access Repository

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001-User Access  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide a repository layer for managing user access control, including role assignments, capability evaluation, and access matrix calculations. The repository shall interface with HRM data structures for position/team inheritance and support direct employee RBAC role overrides.

## Functional Details
1. The user access repository shall interface with the HRM employee table and position mappings
2. Support for retrieving effective RBAC role IDs considering the inheritance hierarchy:
   - Direct employee RBAC roles (highest precedence)
   - Position-inherited RBAC roles 
   - Default roles (fallback)
3. Methods for querying and managing access capabilities for specific records
4. Caching of effective team IDs for performance optimization
5. Integration with the RBAC service layer for capability evaluation

## Technical Implementation
- Interface: `Ksfraser\RBAC\Repository\UserAccessRepositoryInterface`
- Implementation: `Ksfraser\RBAC\Service\AccessRepository.php`
- Database tables: `hrm_employee_roles`, `hrm_position_roles`, `rbac_role_matrix`
- Inheritance logic: Direct Role > Position Role > Default Role

## Linked Use Cases
- UC-001: Add Access Rule
- UC-002: Assign Role to User
- UC-003: View Access Matrix
- UC-004: Revoke Access
- UC-005: Audit Access Changes

## Dependencies
- ksf_FA_HRM module for position/team data
- ksf_RBAC service layer for capability evaluation