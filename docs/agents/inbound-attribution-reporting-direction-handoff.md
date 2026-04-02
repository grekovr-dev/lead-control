# Inbound Attribution Reporting — Direction / Handoff

## Purpose

This document captures a reporting direction that should be considered for future implementation.

It is based on the current inbound capture model already present in the codebase:
- `Click` stores raw entry attribution
- `Visit` stores `firstAttribution` and `lastAttribution`
- `Lead` stores both:
  - `visitAttribution`
  - `visitorAttribution`

This is not an instruction to implement immediately.
It is a semantics handoff and a decision aid for future reporting work.

---

## Business Framing

The system now supports two valid ways to understand lead attribution:

1. Session-level attribution
- answers:
  - "Which source brought the visit in which the lead happened?"
- this is the best view for:
  - campaign performance
  - retargeting
  - visit-to-lead efficiency

2. Visitor-level acquisition attribution
- answers:
  - "Which source brought this person into the system for the first time?"
- this is the best view for:
  - acquisition quality
  - first-touch marketing effectiveness
  - long-tail conversion analysis

These are different questions.
They must not be collapsed into one report.

---

## Current Capture Path

The current path from traffic to lead is:

1. Landing open
- Laravel resolves or creates `VisitorId`
- Laravel resolves pending attribution snapshot
- pending attribution is stored in cookies

2. Click
- `RegisterClickAction` persists `Click`
- `RegisterClickAction` is the only capture action that may create a new `Visit`
- `Click` carries:
  - `visitorId`
  - `visitId`
  - `landingUrl`
  - full `Attribution` including `referrer`

3. Visit lifecycle
- `ResolveCurrentVisitAction`:
  - continues the latest visit if session rule passes
  - otherwise creates a new visit
- on new visit:
  - `Visit.firstAttribution = Click.attribution`
  - `Visit.lastAttribution = Click.attribution`
- on continued visit via click:
  - `Visit.lastAttribution` is updated from the click

4. Non-click continuation
- `ContinueCurrentVisitAction`:
  - never creates a visit
  - only prolongs the latest existing visit
- `Touch` updates:
  - `Visit.lastTouchedAt`
- `Touch` does not update attribution

5. Lead creation
- `CreateLeadFromFormAction`
- `CapturePhoneClickAction`
- both create `Lead` from the current visit
- current lead semantics:
  - `Lead.visitAttribution = Visit.firstAttribution`
  - `Lead.visitorAttribution = firstAttribution of the first visit of this visitor`
  - `Lead.landingUrl = Visit.landingUrl`

Important:
- `Lead` is no longer supposed to derive attribution from cookies directly
- `Lead` attribution is now visit-backed and visitor-backed

---

## Reporting Implication

The system now has two separate attribution truths for leads:

1. `Lead.visitAttribution`
- attribution of the visit that produced the lead

2. `Lead.visitorAttribution`
- attribution of the first visit of the visitor who produced the lead

This means there should be two separate reporting interpretations:

1. Visit attribution reporting
2. Visitor acquisition reporting

---

## Visit Attribution Funnel

### Business meaning

This report answers:
- "Which visit sources convert into leads?"

### Canonical buckets

- clicks: `Click.attribution_*`
- visits: `Visit.first_attribution_*`
- leads: `Lead.visit_attribution_*`

### Recommended KPI

- `visit_to_lead_conversion_rate = leads / visits`

### Why this report matters

Use it for:
- channel performance
- campaign quality
- remarketing / revisit session effectiveness
- landing-to-lead efficiency at session level

### Important rule

This report is about visits.
It should not use `Lead.visitorAttribution`.

---

## Visitor Acquisition Funnel

### Business meaning

This report answers:
- "Which first-touch sources bring people who eventually become leads?"

This is a separate report slice.
It must not be treated as a renamed or slightly adjusted `Visit Attribution Funnel`.

### Cohort period

The report period should mean:
- the period of the visitor's first visit

This means:
- denominator = visitors whose first visit started in the selected period
- numerator = leads produced by those visitors, even if the lead itself was created later

This is intentionally cohort-like.
Do not reinterpret it as:
- "leads created in the same period"
- "visits started in the same period"
- "all activity that happened in the same period"

### Canonical numerator

- leads grouped by:
  - `Lead.visitor_attribution_source`
  - `Lead.visitor_attribution_medium`
  - `Lead.visitor_attribution_campaign`

### Canonical denominator

- first visits grouped by:
  - `Visit.first_attribution_source`
  - `Visit.first_attribution_medium`
  - `Visit.first_attribution_campaign`
- but only one first visit per `visitor_id`

### Recommended KPI

- `first_visit_to_lead_conversion_rate = leads / first_visits`

### Why this report matters

Use it for:
- acquisition quality
- first-touch source analysis
- identifying channels that bring people, not just sessions

### Important rule

Do not divide `Lead.visitorAttribution` by:
- all raw clicks
- all visits
- revisit sessions

That would mix visitor-level attribution with session-level denominator and distort the metric.

Also do not filter leads by `Lead.created_at` for this report.
The report should answer:
- "Among visitors first acquired in this period, how many eventually became leads?"

---

## Why `lastAttribution` Should Not Be Used In Conversion Reports

`Visit.lastAttribution` is still useful, but not as the primary source of truth for funnel reporting.

It is useful for:
- operational context
- recent touch context inside a visit
- understanding the latest click that prolonged a visit

It should not be used as the main attribution basis for conversion reports because:
- it represents the latest click context, not the attribution basis of the visit
- it can change inside the same visit
- it can blur the difference between visit attribution and revisit behavior

Recommended rule:
- keep `lastAttribution` for context and drill-down
- do not use it as the canonical grouping field for lead conversion reporting

---

## Recommended Reporting Structure

Instead of one universal attribution report, consider two separate reports:

1. `Visit Attribution Funnel`
- source of truth:
  - `Click.attribution`
  - `Visit.firstAttribution`
  - `Lead.visitAttribution`

2. `Visitor Acquisition Funnel`
- source of truth:
  - first visit per visitor
  - `Lead.visitorAttribution`

These two reports answer different business questions and should stay separate.

---

## Suggested Future Read-Side Direction

If this direction is implemented later, the most likely shape would be:

1. Keep the current attribution funnel report as session-oriented
- or rename it explicitly if needed

2. Add a second read-side slice for visitor acquisition
- likely a separate query/handler/read model
- do not overload the existing attribution funnel report with mixed semantics

3. Keep `Origin Funnel Report` separate
- it answers a different question:
  - conversion method (`form`, `phone_click`, `messenger_click`)
- not traffic-source attribution

---

## Suggested Naming To Consider

Business/UI naming ideas:

- `Visit Attribution Funnel`
- `Visitor Acquisition Funnel`

Internal naming ideas:

- `GetVisitAttributionFunnelReport`
- `GetVisitorAcquisitionFunnelReport`

This naming is only a proposal.
The important part is semantic separation, not the exact label.

---

## Implementation Caution

If this direction is implemented:

- do not mix `visitAttribution` and `visitorAttribution` into one aggregate report
- do not use `Visit.last_attribution_*` as a conversion denominator or numerator grouping field
- keep first-touch and session-touch reporting conceptually separate
- prefer explicit report semantics over "one report with switches" unless there is a very strong reason

---

## Summary

The project now supports two attribution views for leads:

1. visit-level attribution
2. visitor first-touch attribution

Because of that, future reporting should likely split into:

1. a session conversion report
2. a visitor acquisition report

`Visit.lastAttribution` should remain contextual, not canonical, for conversion reporting.
