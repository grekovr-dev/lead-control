# Inbound Backoffice Auth / RBAC — Handoff / Status

## Recommended Branch

- `feature/backoffice-auth-rbac`

This branch is for the next Laravel delivery / authorization phase:

→ add real backoffice authentication and role/permission control before the write path is updated to attach actor identity to `LeadNote`

This is NOT the branch where:
- the Inbound core is redesigned
- backoffice screens are rebuilt from scratch
- capture flow is reworked

The existing Inbound core and the current Laravel backoffice baseline should be treated as the starting point.

---

## Why This Work Is Needed

The backoffice now needs real access control instead of being only “visually present”.

Two business needs are already forcing this direction:

1. Operational access separation
- there are different backoffice actors
- `admin` and `manager` must not have the same rights

2. Lead authorship
- `LeadNote` needs an attached user identity
- note authorship cannot remain anonymous if the operational trail must be preserved

This means:
- simple “open admin page” protection is not enough
- the system needs actual users, roles, and permissions

---

## Current State

### Laravel Backoffice

The Laravel backoffice already exists as the current delivery surface.

Important reality:
- backoffice routes and UI are already part of the project baseline
- the current problem is not “build the first admin shell”
- the current problem is “make backoffice access controlled and attributable”

### Inbound Operational Core

Already implemented in `src/Inbound`:
- `Lead`
- `LeadNote`
- `LeadStatusTransition`
- `ListLeads`
- `GetLeadDetails`
- `GetLeadTimeline`
- `AddLeadNoteAction`
- `ChangeLeadStatusAction`

Important:
- `LeadNote` is operational, not decorative
- `LeadStatusTransition` is append-only history
- `ListLeads` is a current-state operational list
- notes and history belong to `LeadDetails` / `LeadTimeline`

### Production Baseline

Already resolved on the VPS:
- production deploy flow exists
- backup-before-deploy exists
- manual release flow exists
- production env contract exists
- production stack already runs

Important:
- do NOT reopen deployment work inside this branch
- do NOT mix auth/RBAC work with production bootstrap concerns

---

## Business Goal

Build a minimal but real backoffice authorization layer.

Meaning:
- users can log in
- roles and permissions exist
- `admin` and `manager` are different
- note creation can store the acting user
- the backoffice is not publicly open by accident

This branch is about:
- authentication
- authorization
- initial users
- role/permission wiring
- operational authorship

Important:
- RBAC lives in the Laravel delivery layer only
- `src/Inbound` should receive only the acting user id, not RBAC mechanics

This branch is NOT primarily about:
- new reporting semantics
- production deployment
- backoffice UI redesign

---

## Recommended Authorization Package

Use:
- `spatie/laravel-permission`

Why this is the preferred path:
- it is a practical Laravel RBAC solution
- it supports roles and permissions cleanly
- it fits the current scale of the project
- it keeps the implementation minimal without inventing custom RBAC infrastructure too early

Do NOT build a custom permission engine unless there is a strong reason.

---

## Main Plan For This Branch

### 1. Add RBAC Package Support

Introduce the roles/permissions package into the Laravel app.

Expected direction:
- install `spatie/laravel-permission`
- publish config and migrations
- keep the change minimal and explicit

Important:
- do not mix this with business UI work
- do not move domain logic into auth setup

### 2. Update `User` For Roles And Permissions

Make the Laravel user model compatible with RBAC.

Expected direction:
- add the package trait to `User`
- keep the model otherwise stable

### 3. Apply RBAC Migrations

Create the database tables required for roles and permissions.

Expected direction:
- roles table
- permissions table
- pivot tables for assignments

### 4. Seed Initial Roles, Permissions, And Users

Create the first operational access model.

Expected direction:
- create `admin` role
- create `manager` role
- define permissions explicitly
- create two initial users:
  - admin
  - manager
- assign permissions to roles

Important:
- keep seeds idempotent
- use env-driven credentials for seeded users
- do not hardcode secrets

### 5. Protect Backoffice Routes With Authentication

Close the backoffice surface behind login.

Expected direction:
- apply `auth` middleware to backoffice routes
- keep public landing and capture flow open
- do not break current public traffic flow

Important:
- backoffice should not remain casually open
- auth is the first guardrail before permissions

### 6. Add Backoffice Login / Logout Flow

Provide a usable way to enter and leave the backoffice.

Expected direction:
- login page
- logout action
- post-login redirect into backoffice

Important:
- keep the UI simple
- keep all user-facing copy in Ukrainian
- do not overbuild the login experience

### 7. Wire Permissions Into Backoffice Screens And Actions

Use permissions for actual access decisions.

Expected direction:
- protect screens and actions with permissions
- `admin` should have full access
- `manager` should have a reduced operational subset

Suggested permission groups:
- `dashboard.view`
- `reports.view`
- `leads.view`
- `leads.note.create`
- `leads.status.update`
- `users.manage`
- `horizon.view`

### 8. Attach `LeadNote` To The Acting User

Make note authorship visible and recoverable.

Expected direction:
- add `author_user_id` to lead notes
- write the authenticated user id on note creation
- keep note authorship available for operational audit

Important:
- this is one of the main reasons this branch exists
- do not leave note creation anonymous

### 9. Decide Horizon Access Separately

Keep Horizon access as a separate operational decision.

Expected direction:
- either keep it admin-only
- or leave it behind its existing gate
- do not automatically grant manager access

Important:
- Horizon is tooling, not normal backoffice business flow
- keep it separate from the main auth/RBAC rollout

---

## Practical Execution Order

If a new agent needs a strict order, use this sequence:

1. add RBAC package support
2. update `User`
3. apply RBAC migrations
4. seed roles / permissions / users
5. protect backoffice routes with `auth`
6. add login / logout flow
7. wire permissions into screens and actions
8. attach `LeadNote` to the acting user
9. decide Horizon access separately

After this branch is implemented, update `AGENTS.md` with the agreed RBAC/auth rules so the repo-level guidance reflects the new backoffice access model.

This is the intended stepwise implementation path.

---

## Testing Expectations

Use the existing test layers consistently:

### `src/Tests/Inbound`
- Domain/Application tests for RBAC-adjacent use case behavior where no Laravel container is needed
- use this only for behavior that can stay framework-free

### `apps/web/tests/Unit`
- lightweight Laravel-specific unit and wiring tests
- RBAC package bindings
- auth / permission integration seams
- keep these thin and framework-focused

### `apps/web/tests/Feature`
- DB-backed integration tests
- role / permission persistence
- seed behavior
- backoffice route protection
- login / logout flow
- lead note authorship persistence

Important:
- do not duplicate pure domain behavior tests in Laravel feature tests
- do not push Laravel auth mechanics into `src/Tests/Inbound`

---

## Important Design Rules

- Backoffice is NOT CRUD
- Laravel is a delivery layer, not the home of business rules
- controllers must stay thin
- Domain and Application must not depend on Laravel auth internals
- do NOT design a custom RBAC framework unless necessary

For this branch specifically:
- prefer `spatie/laravel-permission` over inventing a new role system
- keep the operational backoffice flow intact
- keep public landing / capture flow open
- keep all user-facing admin copy in Ukrainian

---

## What Agent MUST NOT Do

- do not redesign architecture
- do not move logic from `src/Inbound` into Laravel auth code
- do not rework capture
- do not make backoffice public by accident
- do not introduce Russian UI copy
- do not hardcode seeded credentials
- do not treat Horizon as part of the core backoffice auth flow

---

## Mental Model

Think in 2 layers:

1. Laravel Auth / Delivery Layer
- handles login
- handles route protection
- handles permission checks
- handles who is allowed to operate the backoffice

2. Inbound Core
- already implemented
- owns lead notes, lead history, and operational behavior
- should only receive the authenticated actor id, not auth complexity

The shared business need is:

`User -> Role -> Permission -> Backoffice Action -> LeadNote / LeadStatusTransition`

The system should preserve attribution for operations, not just for traffic.

---

## Summary

You are not building a new backoffice from scratch.

You are adding real authorization and operational identity to the existing backoffice surface.

Your task on this branch is:
- add RBAC
- close the backoffice behind login
- create the first admin and manager users
- preserve authorship for lead notes
- keep the architecture stable

Do not redesign anything beyond what is needed for access control and authorship.
