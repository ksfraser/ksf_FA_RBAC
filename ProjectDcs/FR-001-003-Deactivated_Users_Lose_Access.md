# FR-001-003-Deactivated Users Lose Access

**Module**: ksf_FA_RBAC  
**Satisfies**: BR-001-Access Control  
**Priority**: High  
**Status**: Draft  

## Description
A deactivated FA user shall lose all RBAC access **immediately**. Deactivation must not rely on a batch job or cache expiry: the very next access-level query or capability check for that user must return "no access".

## Functional Details
1. The access-level resolver (library FR-037/038) MUST treat a deactivated user as having **no effective teams** and **no grants**
2. Team membership queries MUST exclude memberships whose owning user is deactivated
3. The session access-level cache (library FR-042) MUST be invalidated/cleared for a user the moment they are deactivated
4. An active session of a deactivated user MUST NOT grant any RBAC-level access; record-level checks return deny
5. Deactivation is a soft-delete (`inactive = 1` on `0_users`); the audit log MUST record the deactivation (see FR-030)

## Verification
- Deactivating a user's account immediately removes their access-level cache and team resolution — no scheduled job required
- A deactivated user's outstanding session cannot read or modify any RBAC-gated record
- Reactivating the user restores access only after re-login re-provisions (FR-001) and re-resolves access levels

## Technical Implementation
- Access-level resolver checks `0_users.inactive` (or the platform's user-status source) on every resolution
- Effective-team resolution filters membership by user status
- Hooks/adapters clear `$_SESSION['ksf_rbac']['access_levels']` for the deactivated user

## Linked Requirements
- BR-001-Access Control
- FR-001-001-Access_Repository.md
- Library: FR-037, FR-038, FR-042
