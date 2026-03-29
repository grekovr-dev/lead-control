# AGENTS.md

## Project Overview

lead-control is a lead generation and conversion tracking system.

The primary goal:
- capture inbound traffic (clicks, visits, touches)
- convert it into leads
- provide a backoffice to monitor funnel performance
- enable attribution and conversion analytics

This is NOT just a CRUD system.

This is a funnel-aware system where:
- one Visitor can generate multiple clicks
- clicks form visits
- visits generate touches
- touches may result in leads

The system must preserve causality and attribution across all stages.

---

## Business Context

The system is designed for:
- landing pages (ad traffic)
- phone click tracking (tel:)
- form submissions
- attribution tracking (UTM, source, etc.)
- contractor lead processing (future)

Core business value:
- understand conversion funnel
- measure performance (click → lead)
- attribute leads to traffic sources
- optimize marketing spend

---

## Language Policy

### Communication Language

- The project owner communicates in **Russian**
- You MUST understand and respond correctly to Russian instructions

---

### Product Language

- All **user-facing content MUST be in Ukrainian**
- This includes:
  - UI text
  - labels
  - messages
  - landing page content
  - backoffice UI

---

### Code Language

- Code MUST be written in **English**
- This includes:
  - class names
  - method names
  - variables
  - file names

---

### Comments

- Prefer English for code comments
- Russian may be used for explanations outside code
- Do NOT mix Ukrainian into code

---

### Important Rule

NEVER:
- translate business logic into Russian
- generate UI text in Russian
- mix languages randomly

---

## Core Domain Concepts

Entities:

- VisitorId — user identity (cookie-based)
- Click — entry point (ad click, UTM)
- Visit — session-like grouping
- Touch — interaction event within a Visit
- Lead — conversion result
- LeadNote — operational note attached to a lead
- LeadStatusTransition — append-only lead status history record

Important rules:

- VisitorId is the backbone
- attribution originates at Click
- latest Visit per Visitor is critical
- Click is visitor-scoped
- Touch is visit-scoped
- Lead carries both VisitorId and VisitId
- do NOT assume a direct Click → Visit foreign-key style relation

---

## Architecture Overview

The project follows a DDD-inspired architecture.

### Domain (src/Inbound/Domain)

Contains:
- entities
- value objects
- enums
- repository interfaces

Rules:
- no Laravel
- no framework dependencies
- pure business logic only

---

### Application (src/Inbound/Application)

Contains:
- Actions (write-side use cases)
- Queries (read-side use cases)
- DTO / View models

Responsibilities:
- orchestrate domain
- implement use cases
- prepare data for UI

Internal organization:
- scenario-first structure
- one use case = one folder
- examples:
  - `Application/Actions/Backoffice/AddLeadNote/...`
  - `Application/Queries/Backoffice/GetLeadTimeline/...`

---

### Infrastructure (src/Inbound/Infrastructure)

Contains:
- Eloquent models
- repository implementations
- persistence logic

Rules:
- implements Domain interfaces
- may depend on Laravel

Internal organization:
- adapter-type-first structure
- do NOT mirror the full Application use-case tree
- examples:
  - `Infrastructure/Persistence/Eloquent/EloquentLeadRepository.php`
  - `Infrastructure/Persistence/Eloquent/ReadModel/EloquentLeadTimelineReadModel.php`

---

### Laravel Integration Layer (apps/web)

Contains:
- Controllers
- FormRequests
- Resolvers (VisitorId, Attribution)
- Routes

Rules:
- framework-first structure
- no business logic
- must not leak into Domain

---

## Architectural Principles

### 1. Strict Layer Separation

- Domain must not depend on Laravel
- Application must not depend on Eloquent
- Controllers must not contain business logic

---

### 2. Thin Controllers

Controllers:
- validate input (FormRequest)
- call Application layer
- return response

No logic.

---

### 3. Application = Orchestration Layer

Application:
- coordinates repositories
- builds aggregates
- executes use cases

---

### 4. Repository Pattern

- defined in Domain
- implemented in Infrastructure

Avoid:
- direct Eloquent in Application
- leaking models outside Infrastructure

---

### 5. Read / Write Separation

- Actions → write-side
- Queries → read-side

Backoffice must use:

Query → Handler → ReadModel → View

Do NOT use write repositories for dashboard.

---

### 6. Attribution Integrity

Attribution:
- originates from Click
- flows through Visit → Touch → Lead
- must always be recoverable via VisitorId

---

### 7. VisitorId as Backbone

- stored in cookie
- links all events

Rules:
- resolved in Laravel layer
- not generated inside Domain implicitly

---

## Testing Strategy

### 1. DDD / Application Tests (`src/Tests/Inbound`)

Use for:
- Domain entities
- value objects
- enums
- Application Actions
- Application Queries / Handlers

Rules:
- no Laravel container
- no Eloquent models
- no database
- prefer fakes / stubs / mocks around Domain and Application contracts

---

### 2. Laravel Unit Tests (`apps/web/tests/Unit`)

Use for:
- container wiring
- service provider bindings
- Laravel-specific lightweight units
- framework integration seams that do not need full feature coverage

Rules:
- may boot Laravel
- should stay thin and framework-focused
- do NOT duplicate Domain/Application behavior tests here

---

### 3. Integration / Feature Tests (`apps/web/tests/Feature`)

Use for:
- HTTP delivery
- controller behavior
- Eloquent repository implementations
- Eloquent read models
- DB-backed integration of Application use cases

Rules:
- use real Laravel integration when persistence or framework behavior matters
- keep business rules owned by Domain/Application, not reimplemented in test setup

---

## Backoffice Philosophy

Backoffice is NOT CRUD.

It is a funnel visualization tool.

It must support:
- conversion metrics
- funnel breakdown
- attribution analysis
- operational lead handling

Examples:
- click → lead conversion rate
- visits per visitor
- touches before lead
- current lead status
- lead notes
- lead history / timeline

Operational screen rules:
- `ListLeads` is a current-state operational list
- history, notes, and status transitions belong to `LeadDetails` / `LeadTimeline`
- do NOT turn the leads list into a mini-timeline by default

---

## Technical Constraints

- PHP 8.3
- Laravel (integration layer only)
- MySQL
- Redis
- Docker

---

## Coding Rules for Agents

### DO

- follow existing structure
- keep changes minimal
- respect layers
- reuse patterns
- create small focused classes

---

### DO NOT

- move files across layers without instruction
- introduce Laravel into Domain
- bypass Application layer
- add hidden magic

---

## Naming Conventions

- Actions: *Action
- Queries: *Query, *Handler
- DTO/View: *View, *ReadModel
- Repositories: *Repository

---

## When Uncertain

- prefer minimal change
- do not refactor large parts
- do not invent new architecture
- follow existing code patterns

---

## Key Goal for Agents

Do NOT redesign the system.

Your goal:
- implement use cases correctly
- preserve domain integrity
- keep architecture clean
- maintain funnel logic

---

## Summary

This system is about:

- tracking user journey
- preserving attribution
- measuring conversions
- enabling business decisions

Respect the domain. Do not simplify it.
