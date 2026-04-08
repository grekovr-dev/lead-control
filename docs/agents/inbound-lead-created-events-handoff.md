# Inbound Lead Created Events — Handoff / Status

## Recommended Branch

- `feature/inbound-lead-created-events`

This branch is for the next event-driven continuation slice in Inbound:

→ publish `LeadCreated` after successful lead creation and trigger async manager notification through queue jobs

This is NOT the branch where:
- the Inbound core is redesigned from scratch
- a generic event store is introduced
- all future notification channels are implemented at once
- old capture flow semantics are revisited again

The current `src/Inbound` capture model and transaction boundaries should be treated as the baseline.

---

## Why This Work Is Needed

The project now creates leads correctly, but post-create reactions are still missing.

The first real business need is:
- after a lead is created, notify the manager in Telegram

This should be implemented without polluting:
- controllers
- capture actions
- domain entities with Laravel mechanics

The desired architecture is:
- domain records business facts
- application publishes those facts
- reactions are subscribed independently
- external side effects are executed asynchronously via queue jobs

---

## Current State

### Capture Write Path

The relevant production paths already exist:
- `CreateLeadFromFormAction`
- `CapturePhoneClickAction`

Important reality:
- these are the only current production use cases that create `Lead`
- both already use `TransactionManager`
- `CapturePhoneClickAction` sometimes creates `Touch` instead of `Lead`

### Lead Creation Contract

`Lead` is currently instantiated in two different contexts:

1. real creation in write-side use cases
2. hydration from persistence in `EloquentLeadRepository`

Important:
- because of this, `LeadCreated` must NOT be recorded in the plain constructor
- otherwise repository hydration would falsely create domain events

This means:
- the constructor must remain hydration-safe
- a separate creation path is needed for event recording

### Queue / Async Baseline

Laravel queue infrastructure already exists in the app baseline.

Important:
- queue should contain jobs, not domain events
- retries should belong to jobs
- lead creation itself must not depend on Telegram success

---

## Target Flow

The intended flow is:

1. HTTP request enters the controller
2. controller calls a capture action
3. action creates `Lead` through a creation-specific domain path
4. `Lead` records `LeadCreated`
5. repository saves `Lead`
6. transaction commits
7. action publishes released domain events through `EventBus`
8. subscribed reaction handles `LeadCreated`
9. reaction schedules an async job
10. worker executes the job
11. job loads notification payload through read-side
12. job calls `TelegramClient`

Important:
- events are published after successful commit
- jobs are queued because of reactions
- workers execute jobs, not domain events

---

## Architectural Rules

### 1. Domain Must Stay Clean

Domain may:
- define events
- record events
- release events

Domain must NOT:
- dispatch Laravel events
- dispatch jobs
- know about Horizon
- know about Telegram

### 2. Event Is A Fact, Not A Job

`LeadCreated` means:
- a lead was created

It is not:
- a queue task
- a Telegram action
- a delivery concern

### 3. Queue Is For Side Effects Only

Queue should be used only for:
- external APIs
- slower technical work
- retryable side effects

Do NOT place domain events into the queue.

### 4. Publish After Commit

Because lead-creating actions already use `TransactionManager`:
- `Lead` must be saved inside the transaction
- `EventBus->publish(...)` must happen after `run(...)` returns successfully

Do NOT publish from inside the transaction closure.

### 5. One Event, Many Reactions

`LeadCreated` should stay generic and reusable.

Today:
- `NotifyManagerAboutNewLead`

Possible future additions:
- `NotifyClientAboutLeadCreated`
- `CreateLeadFollowUpReminder`
- `SyncLeadCreatedToExternalSystem`

Do NOT design the first reaction in a way that blocks future parallel reactions.

### 6. Keep Schedulers Narrow

Schedulers should be reaction-specific.

Good:
- `ManagerLeadNotificationScheduler`
- `ClientLeadNotificationScheduler`

Avoid:
- one huge scheduler responsible for every possible channel and recipient

The extensibility model should be:
- one event
- many reactions
- each reaction has its own scheduler/job/channel implementation

---

## Main Plan For This Branch

### 1. Add Domain Event Support To `Lead`

Introduce lightweight recorded-events support in the domain.

Expected direction:
- add internal recorded-events storage
- add `recordThat(...)`
- add `releaseEvents(): array`

Important:
- keep the plain constructor hydration-safe

### 2. Add `LeadCreated` Domain Event

Create the first event:
- `LeadCreated`

The event should contain only essential identifiers and timing:
- `leadId`
- `visitId`
- `visitorId`
- `occurredAt`

Important:
- do not pack UI strings or notification text into the event

### 3. Introduce A Creation-Specific Path For `Lead`

Add a domain creation method such as:
- `Lead::create(...)`

Expected behavior:
- build a valid `Lead`
- record `LeadCreated`
- return the aggregate

Important:
- repository hydration must continue using the plain constructor
- do NOT make repository reads produce events

### 4. Add `EventBus` To Application

Introduce an application abstraction:
- `EventBus`

Expected API:
- `publish(object ...$events): void`

Important:
- Application depends only on the abstraction
- no Laravel event system inside `src/Inbound/Application`

### 5. Publish Events From `CreateLeadFromFormAction`

Update the form lead flow.

Expected direction:
- create the lead through `Lead::create(...)`
- save it inside `TransactionManager->run(...)`
- return the `Lead` from the transaction
- publish `releaseEvents()` only after successful commit

Important:
- no Telegram logic in the action
- no job dispatch from the action

### 6. Publish Events From `CapturePhoneClickAction`

Update the phone-click lead flow.

Expected direction:
- when the flow creates a `Lead`, publish its released events after commit
- when the flow returns `Touch`, publish nothing

Important:
- event publishing must reflect the actual business result

### 7. Add The First Reaction: `NotifyManagerAboutNewLead`

Create the first application reaction to `LeadCreated`.

Expected direction:
- listener/reactor accepts `LeadCreated`
- listener does not send Telegram directly
- listener delegates to a scheduler abstraction

Important:
- this is a reaction layer, not infrastructure execution

### 8. Add `ManagerLeadNotificationScheduler`

Create a scheduler abstraction for manager notification.

Expected direction:
- application listener depends on this interface
- infrastructure implementation will dispatch the queue job

Important:
- keep it specific to manager notification
- do not turn it into a universal notification gateway

### 9. Implement `LaravelEventBus`

Add the Laravel-side implementation of `EventBus`.

Expected direction:
- use Laravel event dispatcher internally
- route `LeadCreated` to subscribed reactions

Important:
- do not introduce a custom event framework unless there is a strong reason

### 10. Implement Scheduler Through Queue

Create the infrastructure implementation of:
- `ManagerLeadNotificationScheduler`

Expected direction:
- dispatch `SendManagerLeadCreatedTelegramJob`

Important:
- this is the first point where queue appears in the flow
- queue contains jobs, not events

### 11. Add `SendManagerLeadCreatedTelegramJob`

Create the async job that performs the notification work.

Expected direction:
- job accepts `leadId`
- job loads notification data through read-side
- job calls `TelegramClient`

Important:
- keep retries at the job level
- failure here must not roll back lead creation

### 12. Use Read-Side For Notification Payload

Do not query random Eloquent models directly in the job.

Expected direction:
- either reuse an existing read-side query if it is good enough
- or introduce a dedicated notification payload query/view

Start pragmatically:
- first evaluate whether the existing lead details read-side is sufficient

Important:
- if notification payload diverges from backoffice detail semantics, split it later into a dedicated query

### 13. Add `TelegramClient`

Create the infrastructure client for Telegram delivery.

Expected direction:
- encapsulate API call details
- configure through Laravel config / env

Important:
- Telegram should exist only in infrastructure / Laravel side
- do not leak it into domain or application

### 14. Wire Everything In Laravel

Through service providers:
- bind `EventBus -> LaravelEventBus`
- bind `ManagerLeadNotificationScheduler -> ...`
- register `LeadCreated -> NotifyManagerAboutNewLead`
- register `TelegramClient`

Important:
- keep wiring explicit
- do not hide event subscriptions in surprising places

### 15. Add Tests For The Pattern

Expected minimum coverage:

Domain:
- `Lead::create()` records `LeadCreated`
- hydration path does not record events

Application:
- `CreateLeadFromFormAction` publishes events after successful create
- `CapturePhoneClickAction` publishes only on lead creation
- `NotifyManagerAboutNewLead` calls the scheduler

Infrastructure:
- `LaravelEventBus` delivers the event to the reaction
- scheduler dispatches the job
- job reads payload and calls `TelegramClient`

Important:
- test the commit-aware flow, not just object creation

---

## Practical Execution Order

If a new agent needs a strict order, use this sequence:

1. add domain recorded-events support to `Lead`
2. add `LeadCreated`
3. add `Lead::create(...)`
4. add `EventBus`
5. publish from `CreateLeadFromFormAction`
6. publish from `CapturePhoneClickAction`
7. add `NotifyManagerAboutNewLead`
8. add `ManagerLeadNotificationScheduler`
9. implement `LaravelEventBus`
10. implement queue-backed manager scheduler
11. add `SendManagerLeadCreatedTelegramJob`
12. add notification payload read-side usage
13. add `TelegramClient`
14. wire everything in Laravel providers
15. add tests

This order keeps the implementation incremental:
- first the domain fact
- then application publishing
- then the first reaction
- then async technical execution

---

## How To Continue This Work

This handoff is intentionally written as step-by-step execution slices.

Recommended collaboration mode with the next agent:
- "реализуй пункт 1"
- "реализуй пункт 2"
- ...

This helps keep the rollout:
- incremental
- reviewable
- easy to verify after each step

---

## Future Extensibility Reminder

If later the system must also notify:
- the client in Viber
- the client in WhatsApp
- by SMS
- by email

The expected direction is NOT:
- expand `ManagerLeadNotificationScheduler` into a universal channel switchboard

The expected direction IS:
- keep `LeadCreated`
- add another reaction
- add another scheduler
- add another job
- add another client

Example shape:
- `LeadCreated`
  - `NotifyManagerAboutNewLead`
    - `ManagerLeadNotificationScheduler`
      - `SendManagerLeadCreatedTelegramJob`
  - `NotifyClientAboutLeadCreated`
    - `ClientLeadNotificationScheduler`
      - `SendClientLeadCreatedViberJob`

This is the intended extension model.
