# Inbound Backoffice — Handoff / Status

## Current Branch

- `feature/inbound-backoffice-reporting-foundation`

This branch is for the next DDD/Application/Infrastructure phase:

→ build the reporting foundation for Inbound Backoffice

This is NOT the branch where Backoffice core is being invented from scratch.
The operational backoffice core already exists and should be treated as the baseline.

---

## What Already Exists

### Domain

Implemented and already part of the current system:
- Lead
- Click
- Visit
- Touch
- LeadNote
- LeadStatusTransition
- Value Objects such as `VisitorId`, `Attribution`, etc.

Important domain realities:
- `VisitorId` is the backbone
- `Click` is visitor-scoped
- `Touch` is visit-scoped
- `Lead` carries both `visitorId` and `visitId`
- do NOT assume a naive direct `Click -> Visit` FK relation

---

### Application (Write-side)

Capture write-side already exists:
- RegisterClickAction
- RegisterTouchAction
- CreateLeadFromFormAction
- CreateLeadFromPhoneClickAction

Backoffice write-side already exists:
- AddLeadNoteAction
- ChangeLeadStatusAction

Important:
- VisitorId is resolved in Laravel layer
- Attribution is passed from the boundary
- write-side orchestration pattern is already established

---

### Application (Read-side)

Backoffice core read-side is already implemented:
- GetDashboardOverview
- ListLeads
- GetLeadDetails
- GetLeadTimeline
- ListClicks
- ListTouches
- ListVisits

Pattern already established:

`Query -> Handler -> ReadModel -> View`

Do not restart this pattern.
Continue it.

---

### Infrastructure

Already implemented:
- Eloquent models for capture and backoffice persistence
- repository implementations for write-side persistence
- Eloquent read models for existing Backoffice queries
- persistence for lead notes
- persistence for lead status transitions

Important:
- Infrastructure is adapter-type-first
- do NOT mirror the full Application use-case tree
- read-side adapters belong under `Infrastructure/Persistence/Eloquent/ReadModel/...`

---

### Laravel Layer

Already implemented:
- capture endpoints
- validation/FormRequests
- VisitorIdCookieResolver
- attribution resolving in boundary

Backoffice UI is NOT implemented yet.

This branch should NOT start with Laravel controllers/Blade unless explicitly requested.

---

## What Was Completed Before This Branch

The previous phase delivered the operational Backoffice core:
- dashboard overview
- leads list
- lead details
- lead timeline
- clicks/touches/visits drill-down
- lead notes write-side
- lead status change write-side
- lead status transition history persistence

This means:
- operational backoffice core is already done
- current work is no longer about basic backoffice CRUD or core query patterns
- current work is about reporting-oriented read-side expansion

---

## Current Focus

We are now working on:

→ Inbound Backoffice Reporting Foundation

Goal:
- build reporting-oriented read-side slices on top of the existing backoffice core
- stay inside `src/Inbound` for the core reporting logic
- preserve DDD/Application/Infrastructure boundaries
- prepare future reporting UI, but do NOT build it yet unless explicitly requested

---

## Reporting Plan For This Branch

The expected order is:

1. `GetAttributionFunnelReport`
2. `GetOriginFunnelReport`
3. `GetLeadStatusReport`
4. `GetFunnelTrends`

These are separate reporting slices.
Do not collapse them into one universal reporting service.

---

## Immediate Next Step

The next expected task for a new agent on this branch is:

→ implement `GetAttributionFunnelReport`

It should become the first reporting slice.

Minimum intent:
- aggregate by attribution dimensions such as `source / medium / campaign`
- provide funnel counts such as:
  - clicks
  - visits
  - leads
- provide core conversion metrics such as:
  - clicks -> leads
  - visits -> leads

---

## Important Design Rules

- Backoffice is NOT CRUD
- Reporting is NOT a generic BI engine
- Application remains scenario-first
- Infrastructure remains adapter-type-first
- Domain/Application must stay free of Laravel/Eloquent
- Laravel remains a thin integration layer

For reporting:
- prefer dedicated reporting query slices
- keep filters local to each query at first
- do NOT extract a universal reporting abstraction too early

---

## Testing Expectations

Use all 3 existing test layers correctly:

### `src/Tests/Inbound`
- Domain/Application tests
- no Laravel container
- no Eloquent
- no DB

### `apps/web/tests/Unit`
- lightweight Laravel-specific unit/wiring tests

### `apps/web/tests/Feature`
- DB-backed integration tests
- Eloquent repositories
- Eloquent read models
- Laravel delivery behavior where relevant

For each new reporting slice, the expected pattern is:
- DDD/Application test in `src/Tests/Inbound`
- integration test for Eloquent read model in `apps/web/tests/Feature`

---

## What Is NOT Done Yet

- reporting foundation queries
- reporting-specific aggregates and time-series
- reporting UI
- backoffice UI wiring
- Blade/controllers/routes for reporting screens

These remain future work.

---

## What Agent MUST NOT Do

- do not redesign architecture
- do not move layers
- do not rework Capture unless explicitly requested
- do not introduce Laravel into Domain/Application
- do not convert reporting into ad-hoc controller queries
- do not turn the system into CRUD
- do not start UI work unless explicitly requested

---

## Backoffice Operational Rules To Preserve

- `ListLeads` is a current-state operational list
- notes and status history belong to `LeadDetails` / `LeadTimeline`
- do NOT turn `ListLeads` into a mini-timeline by default
- reporting should extend the system, not distort operational screens

---

## Mental Model

Think in 2 connected layers:

1. Operational Backoffice
- work with leads
- inspect lead history
- inspect visits / clicks / touches

2. Reporting Foundation
- explain funnel performance
- explain attribution performance
- explain how traffic converts over time

The shared chain remains:

`Visitor -> Click -> Visit -> Touch -> Lead`

Everything should preserve this chain and its attribution meaning.

---

## Summary

You are not starting Backoffice from zero.

You are continuing from an already-built operational core.

Your task on this branch is:
- extend the read-side into reporting
- preserve existing patterns
- keep the architecture stable
- avoid premature UI work

Do not restart or redesign anything.
