# Inbound Backoffice Reporting UI — Handoff / Status

## Recommended Branch

- `feature/inbound-backoffice-reporting-ui`

This branch is for the next Laravel integration / admin UI phase:

→ build the Reporting UI for Inbound Backoffice on top of the already completed reporting foundation

This is NOT the branch where:
- the reporting core is invented from scratch
- the operational Backoffice UI is rebuilt
- Capture is reworked

The existing `src/Inbound` reporting core and the completed operational Backoffice UI should be treated as the baseline.

---

## What Already Exists Before This Branch

### Operational Backoffice UI

Already implemented in Laravel:
- admin shell
- dashboard
- leads list
- lead details
- lead note form
- lead status update form

Important:
- this operational UI is finished enough to be treated as stable baseline
- do NOT restart the Backoffice shell work
- do NOT move operational lead handling into the reporting branch unless explicitly required

### Operational Backoffice Core In `src/Inbound`

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

Important:
- `ListLeads` is a current-state operational list
- history belongs to `LeadTimeline`
- notes and status transitions already have their correct place

### Reporting Foundation In `src/Inbound`

Already implemented:
- `GetAttributionFunnelReport`
- `GetOriginFunnelReport`
- `GetLeadStatusReport`
- `GetFunnelTrends`

Important reporting semantics already agreed:
- `GetAttributionFunnelReport` is a first-touch acquisition report
- attribution buckets are based on `Visit.firstAttribution`
- `visitsCount` is grouped by `Visit.firstAttribution`
- `leadsCount` is grouped via `lead.visit_id -> visit.firstAttribution`
- `rawClicksCount` is only a reference metric there
- do NOT reintroduce mixed attribution semantics into that report

### Testing Baseline

Already exists:
- DDD/Application tests in `src/Tests/Inbound`
- Laravel unit/wiring tests in `apps/web/tests/Unit`
- integration tests for Eloquent read models in `apps/web/tests/Feature/Inbound`
- Laravel feature tests for the operational Backoffice UI in `apps/web/tests/Feature/App/Http/Controllers/Inbound/Backoffice`

This branch should add Laravel-layer tests where Reporting UI integration and drill-down behavior matter.

---

## Goal Of This Branch

Build the first real Laravel-side Reporting UI while keeping Laravel thin.

Meaning:
- wire the existing reporting queries into Laravel
- create report screens in the admin UI
- add drill-down targets for underlying event data
- preserve DDD/Application/Infrastructure boundaries
- keep controllers thin
- do NOT move reporting logic into Blade or controllers

This branch is about:
- reporting navigation
- reporting screens
- drill-down screens for `Clicks`, `Visits`, and `Touches`

This branch is NOT primarily about:
- new reporting core semantics
- rebuilding the operational leads flow
- redesigning Backoffice shell

---

## Main Scope For This Branch

### Reporting Screens

Expected reporting UI slices:
- reports index
- lead status report
- origin funnel report
- attribution funnel report
- funnel trends

### Drill-Down Targets

Expected drill screens:
- clicks list
- visits list
- touches list

Important:
- these drill screens are not a separate analytics product
- they are target screens that explain the numbers shown in reports
- they should remain simple, operational, and query-driven

---

## Main Plan For This Branch

### 1. Introduce Reporting Laravel Integration Seam

Add the Laravel-side integration needed to consume the existing reporting and drill queries cleanly.

Expected direction:
- bind existing reporting read models/handlers in Laravel
- bind drill read models/handlers for:
  - `ListClicks`
  - `ListVisits`
  - `ListTouches`
- avoid manual object construction inside controllers

Important:
- do NOT leak Eloquent models into controllers
- controllers should depend on handlers/use cases, not persistence models

### 2. Add Reporting And Drill Routes Carefully

Expected direction:
- add report routes:
  - `admin.reports.index`
  - `admin.reports.lead-status`
  - `admin.reports.origin-funnel`
  - `admin.reports.attribution-funnel`
  - `admin.reports.funnel-trends`
- add drill routes:
  - `admin.clicks.index`
  - `admin.visits.index`
  - `admin.touches.index`

Keep routing/controller code thin.

### 3. Reuse The Existing Admin Shell

The shell already exists and should be reused.

Expected direction:
- extend sidebar/header only as much as needed
- introduce a stable entry point for reports
- do NOT rebuild shell behavior from scratch

Important language rule:
- all user-facing admin UI text must be Ukrainian

### 4. Implement A Reports Index Screen

Create a single entry point into reporting.

Expected direction:
- explain the section
- link to the main report slices
- avoid turning this into another dashboard clone

### 5. Implement Drill Screens Before Heavy Reporting Polish

Build the minimal target screens first:
- clicks list
- visits list
- touches list

Reason:
- report drill links should have a stable destination before the report screens rely on them

These screens should:
- use existing `ListClicks`, `ListVisits`, `ListTouches`
- stay simple and operational
- support filters only if the existing queries actually support them

### 6. Implement Lead Status Report

This is the easiest reporting screen and a good first reporting slice.

Expected direction:
- use `GetLeadStatusReport`
- render current lead status distribution
- keep it simple and readable

Important:
- this report does not necessarily need clicks/visits/touches drill by default
- do NOT force drill where it is not semantically natural

### 7. Implement Origin Funnel Report With Drill

Use `GetOriginFunnelReport`.

Expected direction:
- render funnel rows by origin
- add drill links from real count metrics into:
  - clicks
  - visits
  - touches

Important:
- only add drill links where the target query can honestly express the same slice
- do NOT add drill to derived percentages

### 8. Implement Attribution Funnel Report With Drill

Use `GetAttributionFunnelReport`.

Expected direction:
- preserve its agreed first-touch acquisition semantics
- render rows clearly
- add drill links only when they remain semantically honest

Important:
- `rawClicksCount` is a reference metric there
- do NOT quietly reinterpret that report into a mixed click/visit/lead funnel

### 9. Implement Funnel Trends Last

Use `GetFunnelTrends`.

Expected direction:
- start with stable rendering, not flashy charting
- prefer simple visual or tabular output first
- add drill only if existing target queries can honestly represent the period/slice

Important:
- do NOT overstate same-day ratios as cohort conversion without new modeling

### 10. Define A Query-Param Contract For Drill Navigation

This is the most important navigation rule of this branch.

Drill-down should use query params because:
- they describe the target dataset
- they are part of the destination state
- they are shareable and testable

Expected direction:
- explicitly define which query params are passed from reports to:
  - `admin.clicks.index`
  - `admin.visits.index`
  - `admin.touches.index`
- validate/normalize them in Laravel delivery layer

Important:
- unlike “back” navigation, drill filters do belong in query params
- do NOT confuse these 2 concerns

### 11. Keep Report Screens And Drill Screens Conceptually Separate

Reports answer:
- what is happening in the funnel?

Drill screens answer:
- which concrete records sit behind this count?

Do NOT collapse both roles into a single overloaded screen.

### 12. Add Laravel-Layer Tests For Reporting UI

Expected:
- unit/wiring tests for reporting bindings
- feature tests for report screens
- feature tests for drill screens
- feature tests for report-to-drill links

Do NOT duplicate the core read model behavior tests that already belong to the reporting foundation.

### 13. Finish With A Full Reporting/Drill Verification Pass

Expected completion criteria:
- reporting section is available from the admin UI
- click/visit/touch drill targets work
- controllers stay thin
- Blade does not prepare business data
- UI stays Ukrainian
- agreed reporting semantics remain intact

---

## Practical First Scope For A New Agent

If a new agent needs a narrow focus, start with:

1. reporting bindings/wiring in Laravel
2. reports index screen
3. minimal clicks/visits/touches drill screens
4. lead status report
5. origin funnel report

This is the smallest meaningful reporting UI slice.

---

## Important Design Rules

- Reporting UI is NOT a BI rewrite
- Laravel is a delivery layer, not the home of reporting logic
- controllers must stay thin
- Blade must not prepare business data
- Application remains scenario-first
- Infrastructure remains adapter-type-first
- do NOT redesign the existing reporting core without explicit need

For this branch specifically:
- prefer connecting existing reporting read models before inventing new ones
- if drill reveals a real missing seam in `src/Inbound`, change it carefully and minimally
- do NOT let Blade convenience drive new report semantics

---

## What Is Already Resolved And Should Not Be Reopened Lightly

- reporting foundation already exists in `src/Inbound`
- attribution reporting semantics were already clarified
- operational Backoffice UI already exists and is not the main focus now
- client-side “back” navigation in Backoffice details was intentionally solved via `sessionStorage`

Important:
- do NOT bring back server-side `back` query parameters for pure UI return navigation
- drill-down filters are different and are allowed to use query params

---

## What Agent MUST NOT Do

- do not redesign reporting architecture
- do not move reporting logic from `src/Inbound` into Laravel
- do not make controllers query Eloquent directly for report behavior
- do not rebuild the Backoffice shell from scratch
- do not treat drill screens as a replacement for reports
- do not rework Capture
- do not introduce Russian UI copy

---

## Mental Model

Think in 3 layers:

1. Reporting Core In `src/Inbound`
- already implemented
- owns reporting semantics and read-side preparation

2. Drill Queries In `src/Inbound`
- already implemented
- expose the underlying event lists

3. Laravel Reporting UI
- must connect the existing read-side into usable report and drill screens

The main continuity concern:
- keep reporting semantics stable
- keep navigation honest
- keep Laravel thin
