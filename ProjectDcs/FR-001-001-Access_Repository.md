# FR-001-001-Access Repository

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001-Access Control  
**Priority**: High  
**Status**: Draft  

## Description
The system shall provide a repository layer for managing access control, including team memberships, record access grants, capability evaluation, and access-matrix calculations. The repository shall interface with HRM data structures for position/team inheritance and support direct team-based grant overrides.

## Functional Details
1. The access repository shall provide team and record-access persistence:
   - `FaTeamRepository` — teams, memberships, approvers, effective team IDs
   - `FaRecordAccessRepository` — xref grants, reassignment, access JOIN fragments
2. Support for retrieving effective RBAC team IDs considering the inheritance hierarchy:
   - Direct team membership (highest precedence)
   - Position-inherited teams (from HRM positions)
   - Individual team fallback (`{userId}_individual`)
3. Methods for querying and managing access capabilities for specific records
4. Caching of effective team IDs for performance optimization
5. Integration with the `ksfraser/rbac` library service layer for capability evaluation

## Technical Implementation
- Interface: `Ksfraser\FrontAccounting\Rbac\Contract\DbAdapterInterface`
- Implementations:
  - `Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter`
  - `Ksfraser\FrontAccounting\Rbac\Repository\FaTeamRepository`
  - `Ksfraser\FrontAccounting\Rbac\Repository\FaRecordAccessRepository`
- Database tables: `0_rbac_teams`, `0_rbac_team_members`, `0_rbac_record_access`, `0_rbac_audit_log`
- Inheritance logic: Direct Team > Position Team > Individual Team (library FR-039)

## Linked Use Cases
- UC-001: Add Access Rule

## Dependencies
- ksf_FA_HRM module for position/team data
- `ksfraser/rbac` library service layer for capability evaluation
