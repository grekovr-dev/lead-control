# Inbound Backoffice UI — Handoff / Status

## Current Branch

- `feature/inbound-backoffice-ui-foundation`

This branch is for the Laravel integration / admin UI phase:

→ build the first Backoffice UI foundation on top of the already completed Inbound core

This is NOT the branch where:
- Inbound Domain/Application/Infrastructure is invented from scratch
- reporting core is built from zero
- Capture is reworked

The `src/Inbound` backoffice core should be treated as the baseline.

---

## What Already Exists In `src/Inbound`

### Operational Backoffice Core

Already implemented:
- `GetDashboardOverview`
- `ListLeads`
- `GetLeadDetails`
- `GetLeadTimeline`
- `ListClicks`
- `ListTouches`
- `ListVisits`
- `AddLeadNoteAction`
- `ChangeLeadStatusAction`
- `LeadNote`
- `LeadStatusTransition`

Important:
- operational backoffice core already exists
- `ListLeads` is a current-state operational list
- notes and status history belong to `LeadDetails` / `LeadTimeline`
- do NOT turn the leads list into a mini-timeline by default

### Reporting Foundation

Already implemented:
- `GetVisitAttributionFunnelReport`
- `GetOriginFunnelReport`
- `GetLeadStatusReport`
- `GetFunnelTrends`

Important reporting semantics already agreed:
- `GetVisitAttributionFunnelReport` is a visit-level attribution report
- `rawClicksCount` is grouped by `Click.attribution`
- `visitsCount` is grouped by `Visit.firstAttribution`
- `leadsCount` is grouped by `Lead.visitAttribution`
- `rawClicksCount` is only a reference metric there
- do NOT reintroduce mixed visit-level and visitor-level attribution semantics into that report

### Testing Baseline

Already exists:
- DDD/Application tests in `src/Tests/Inbound`
- Laravel unit/wiring tests in `apps/web/tests/Unit`
- integration tests for Eloquent repositories/read models in `apps/web/tests/Feature/Inbound`

This branch should add Laravel-layer tests where UI integration and wiring matter.

---

## Current Laravel Backoffice State

The Laravel backoffice layer currently exists only as a small stub:

- route file: `apps/web/routes/admin.php`
- one controller: `App\Http\Controllers\Inbound\Backoffice\DashboardController`
- basic layout:
  - `resources/views/admin/layouts/app.blade.php`
  - `resources/views/admin/partials/header.blade.php`
  - `resources/views/admin/partials/sidebar.blade.php`
- one dashboard view:
  - `resources/views/admin/dashboard/index.blade.php`

Important reality:
- the current dashboard controller still reads `LeadModel` directly
- this is a temporary stub, not the target architecture
- real Backoffice UI is NOT implemented yet

---

## Goal Of This Branch

Build the first real Laravel-side Backoffice UI foundation while keeping Laravel thin.

Meaning:
- wire existing `src/Inbound` queries/actions into Laravel
- create real operational Backoffice screens
- preserve DDD/Application/Infrastructure boundaries
- keep controllers thin
- do NOT move business logic into Blade or controllers

This branch is about:
- Laravel integration
- admin shell
- operational Backoffice UI

This branch is NOT primarily about:
- new reporting core
- new domain modeling
- redesigning Capture

---

## Main Plan For This Branch

### 1. Introduce Backoffice Laravel Integration Seam

Add the Laravel-side integration needed to consume the existing Inbound core cleanly.

Expected direction:
- bind existing backoffice queries/actions/read models in Laravel
- avoid manual object construction inside controllers
- keep framework wiring inside Laravel layer only

Important:
- do NOT leak Eloquent models into controllers for business behavior
- controllers should depend on use cases / handlers, not raw persistence models

### 2. Restructure Backoffice Controllers / Routes Carefully

Keep Laravel layer framework-first, but make it operationally useful.

Expected direction:
- separate controllers by screen/use case
- expand `admin` routes beyond the current single dashboard
- keep routing/controller code thin

### 3. Upgrade Admin Shell

Turn the current stub layout into a real Backoffice shell.

Expected direction:
- stable sidebar navigation
- page titles / shell consistency
- space for operational screens

Important language rule:
- all user-facing admin UI text must be Ukrainian
- do NOT leave Russian or English UI copy in views

### 4. Implement Dashboard Vertical Slice

Replace the current stub dashboard with a Laravel screen backed by the real Inbound read-side.

Expected direction:
- use `GetDashboardOverview`
- optionally compose in reporting reads only if clearly needed
- no direct aggregate queries in controller
- no `LeadModel::query()->count()` style logic in controller

### 5. Implement Leads Screen

Build the first operational list screen using the existing core.

Expected direction:
- use `ListLeads`
- support filters and pagination through Laravel delivery layer
- add navigation into lead details

### 6. Implement Lead Details Screen

Build the main operational lead screen.

Expected direction:
- use `GetLeadDetails`
- use `GetLeadTimeline`
- wire `AddLeadNoteAction`
- wire `ChangeLeadStatusAction`

This should become the central operational Backoffice screen.

### 7. Leave Reporting UI For A Later Branch

Do NOT try to build all reporting screens inside this first UI-foundation branch unless explicitly requested.

In particular, treat these as out of scope for the first UI phase:
- attribution report screens
- trends report screens
- full reporting navigation surface

### 8. Add Laravel-Layer Tests

This branch should add tests where Laravel integration matters:
- `apps/web/tests/Unit` for bindings and wiring
- `apps/web/tests/Feature` for admin routes/controllers/forms/pages where needed

Do NOT duplicate DDD/Application behavior tests that already belong to `src/Tests/Inbound`.

---

## Practical First Scope

If a new agent needs a narrow operational focus, start with:

1. Backoffice bindings/wiring in Laravel
2. real dashboard screen using `GetDashboardOverview`
3. leads list screen using `ListLeads`
4. lead details screen using `GetLeadDetails` + `GetLeadTimeline`

This is the smallest meaningful UI slice.

---

## Important Design Rules

- Backoffice is NOT CRUD
- Laravel is a delivery layer, not the home of system behavior
- controllers must stay thin
- Blade must not prepare business data
- Application remains scenario-first
- Infrastructure remains adapter-type-first
- do NOT redesign the existing Inbound core without explicit need

For this branch specifically:
- prefer connecting existing read models/actions before inventing new ones
- if a UI need reveals a real missing seam in `src/Inbound`, change it carefully and minimally
- do NOT let the convenience of Blade drive architectural decisions

---

## What Is Already Resolved And Should Not Be Reopened Lightly

- the operational Backoffice core exists in `src/Inbound`
- reporting foundation exists in `src/Inbound`
- `ListLeads` should remain a current-state list
- timeline/history belongs to `LeadTimeline`
- status history is append-only through `LeadStatusTransition`
- attribution reporting semantics were already clarified

---

## What Agent MUST NOT Do

- do not redesign architecture
- do not move logic from `src/Inbound` into Laravel
- do not make controllers query Eloquent directly for core screen behavior
- do not turn Backoffice into generic CRUD screens
- do not start reporting UI by default
- do not rework Capture unless explicitly requested
- do not introduce Russian UI copy

---

## Mental Model

Think in 2 layers:

1. Inbound Core
- already implemented
- owns business behavior and read-side preparation

2. Laravel Backoffice UI
- now being built on this branch
- responsible for delivery, navigation, forms, filters, pagination, views
- must stay thin and integrate with the existing core

The shared funnel remains:

`Visitor -> Click -> Visit -> Touch -> Lead`

The UI must help users observe and operate this funnel.

---

## Summary

You are not building Backoffice core from zero.

You are building the first real Laravel-side Backoffice UI foundation on top of an already completed Inbound core.
