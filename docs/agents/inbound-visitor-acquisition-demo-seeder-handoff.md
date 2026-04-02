# Inbound Visitor Acquisition Demo Seeder - Handoff / Status

## Recommended Branch

- `feature/inbound-visitor-acquisition-demo-seeder`

This branch is for building a scenario-driven demo seeder that makes
`Visitor Acquisition Funnel` useful and understandable on a local database.

This is NOT the branch where:
- reporting semantics are redesigned again
- the current reporting UI is rebuilt
- random demo data is scattered across the system

The goal is to prepare intentional data that demonstrates why
`Visitor Acquisition Funnel` exists separately from `Visit Attribution Funnel`.

---

## Why This Work Is Needed

The current local data is not reliable as a demo baseline for
`Visitor Acquisition Funnel`.

Example problem already observed:
- a bucket may show `0` new visitors
- but still show leads

That may come from historical data inconsistencies, partial migrations, or
non-demo production-like records that do not explain the report well.

This report needs curated data that visibly demonstrates:
- first-touch acquisition
- revisit behavior
- direct revisit after session expiry
- delayed lead creation
- the difference between visit-level and visitor-level attribution

---

## Seeder Name

Use this exact class name:

- `VisitorAcquisitionDemoSeeder`

Important:
- plan the work around this name
- do not introduce a more generic seeder name unless explicitly requested

---

## Main Principle

The seeder must replay realistic inbound flow through application actions.

Meaning:
- new visitors should arrive through `RegisterClickAction`
- visits should be created or continued through the real visit resolution logic
- leads should be created through the real lead actions

Do NOT seed the core demo scenarios by directly inserting:
- visits
- touches
- leads

The whole point is to demonstrate the business flow as the system actually
handles it.

---

## Relevant Capture Flow

The demo data should be built through:
- `RegisterClickAction`
- `CreateLeadFromFormAction`
- `CapturePhoneClickAction`

This is important because:
- a click may continue the current visit
- a click may start a new visit after session expiry
- a lead is attached to the current visit
- but `Lead.visitorAttribution` must still come from the first visit of that visitor

This is exactly what the report needs to make visible.

---

## Key Business Behaviors The Seeder Must Demonstrate

### 1. First click creates the first visit

If a new visitor comes from an attributed click:
- a click is recorded
- a first visit is created with the same initial attribution

### 2. Reload inside active session is not a new visitor and not a new visit

If a visitor reloads the page within the active session:
- another click may be recorded
- the visit continues
- no new visitor appears
- no new visit appears

### 3. Revisit after session expiry may create a new visit

If a visitor returns after session expiry:
- a new click is recorded
- a new visit may be created
- that new visit may have `direct` attribution

### 4. Lead still belongs to first-touch acquisition at visitor level

If a lead is created after a later direct revisit:
- `Lead.visitAttribution` belongs to the current visit
- `Lead.visitorAttribution` still belongs to the first visit of that visitor

This difference is the main reason `Visitor Acquisition Funnel` exists.

---

## Demo Scenarios That Must Exist

### Scenario A - Happy path first-touch conversion

- first click from `google / cpc / spring-sale`
- first visit is created
- form lead is created shortly after

What this should demonstrate:
- a normal first-touch acquisition conversion

### Scenario B - Reload inside the same session

- first click from `facebook / paid-social / lookalike`
- direct reload click happens within the session lifetime
- no new visit is created
- lead is created later

What this should demonstrate:
- reload does not mean a new visitor
- reload does not mean a new visit

### Scenario C - Direct revisit after session expiry

- first click from `google / cpc / spring-sale`
- much later a direct click happens after session expiry
- a new direct visit is created
- lead is created from the new visit

What this should demonstrate:
- `Visit Attribution Funnel` can credit the later direct visit
- `Visitor Acquisition Funnel` still credits the first acquisition source

This is the most important scenario in the whole seeder.

### Scenario D - Strong acquisition bucket

- multiple new visitors from the same first-touch source
- most or all of them become leads

What this should demonstrate:
- a clearly strong acquisition bucket in `Visitor Acquisition Funnel`

### Scenario E - Weak acquisition bucket

- multiple new visitors from one source
- none of them become leads

What this should demonstrate:
- a channel that brings people but not quality leads

### Scenario F - Null attribution bucket

- first click without source / medium / campaign
- visitor becomes a lead later

What this should demonstrate:
- `Без атрибуції` bucket works honestly

### Scenario G - Out-of-cohort lead

- the visitor's first visit happened before the selected report period
- the lead is created later, inside the visible report period

What this should demonstrate:
- the lead must NOT appear in the selected visitor-acquisition cohort
- the report is about first-visit cohorts, not lead creation dates

---

## Date Strategy

Anchor demo data to relative periods, not hard-coded historical dates.

Recommended windows:
- `two months ago`
- `previous month`
- `current month`

Reason:
- built-in presets in the UI should immediately become useful
- the report should still look meaningful when run later

The main demo period should be:
- `previous_month`

That is the period where the clearest visitor-acquisition story should appear.

---

## Expected Reporting Outcome

When opening `Visitor Acquisition Funnel` for `Минулий місяць`, the user should
see clearly different buckets such as:
- one strong paid bucket with visitors and leads
- one weaker bucket with visitors but no leads
- one null-attribution bucket
- one delayed-conversion case that still lands in the first-touch bucket

The report should visibly answer:
- which sources first brought people
- which of those people later became leads

It should not look like a random table with accidental counts.

---

## Relationship With Visit Attribution Funnel

The demo seeder must also make the contrast between two reports visible:

1. `Воронка атрибуції візитів`
- visit-level report
- later direct revisit may change the visit-level lead story

2. `Воронка залучення відвідувачів`
- visitor-level first-touch cohort report
- the same lead may still belong to the original first-touch bucket

The demo data is successful only if opening both reports side by side makes
their difference obvious.

---

## Technical Direction

### Seeder structure

Implement one main seeder:
- `VisitorAcquisitionDemoSeeder`

Inside it, keep the code deterministic and readable.

Recommended private helpers:
- `registerClick(...)`
- `createFormLead(...)`
- `capturePhoneLead(...)`
- `attribution(...)`
- `minutesAfter(...)`
- `hoursAfter(...)`
- `daysAfter(...)`

Use stable readable IDs instead of random values where possible.

### Data creation rule

Prefer invoking application actions from the Laravel container.

The demo flow should pass through real use cases, not through ad hoc SQL.

### Scope discipline

This branch should focus on:
- demo reporting data
- meaningful scenarios
- making `Visitor Acquisition Funnel` understandable

It should not expand into:
- broad fake production datasets
- random factory-driven noise
- unrelated backoffice content

---

## Implementation Plan

### 1. Create the dedicated seeder class

Add:
- `apps/web/database/seeders/VisitorAcquisitionDemoSeeder.php`

Do not add it to `DatabaseSeeder` by default yet.

### 2. Build helper methods for deterministic flow replay

Inside the seeder, add small helpers that:
- resolve actions from the container
- issue capture commands with explicit times and IDs
- keep scenario code readable

### 3. Implement Scenario A

Seed a normal first-touch conversion path.

### 4. Implement Scenario B

Seed reload-within-session behavior and verify it does not create a new visit.

### 5. Implement Scenario C

Seed direct revisit after session expiry and ensure the later lead demonstrates
the split between visit-level and visitor-level attribution.

### 6. Implement Scenario D

Seed a strong acquisition bucket with multiple visitors and strong conversion.

### 7. Implement Scenario E

Seed a weak acquisition bucket with visitors but no leads.

### 8. Implement Scenario F

Seed a null-attribution first-touch bucket.

### 9. Implement Scenario G

Seed an out-of-cohort case where the lead is created later but should not
appear in the selected first-visit period.

### 10. Verify the reports manually on the seeded data

After seeding, open:
- `Воронка залучення відвідувачів`
- `Воронка атрибуції візитів`

Check that the difference between the reports is immediately understandable.

### 11. Only after that decide whether to wire the seeder into a broader demo flow

Do not make it part of default app setup until the seeded scenarios actually
prove useful.

---

## Acceptance Criteria

The work should be considered successful only if:
- `Visitor Acquisition Funnel` looks useful on a fresh local database
- the report clearly shows first-touch cohort behavior
- delayed leads are visible in the correct first-touch bucket
- direct revisit after session expiry is represented correctly
- the difference from `Visit Attribution Funnel` is obvious
- the demo data is deterministic and repeatable

---

## Explicit Non-Goals

- do not add drill-down from `Visitor Acquisition Funnel`
- do not redesign reporting semantics again
- do not replace application actions with raw DB inserts for the core scenarios
- do not generate large random datasets just to make the table non-empty
