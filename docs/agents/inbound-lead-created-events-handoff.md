# Inbound Lead Created Events — Implemented Baseline Handoff

## Branch

- `feature/inbound-lead-created-events`

This branch is complete and should now be treated as the baseline for the first
event-driven continuation slice in Inbound.

Implemented outcome:
- publish `LeadCreated` after successful lead creation
- route the event through `EventBus`
- react with `NotifyManagerAboutNewLead`
- enqueue `SendManagerLeadCreatedTelegramJob`
- load a dedicated notification payload through read-side
- send the manager notification through `TelegramClient`

This branch is NOT:
- a generic event store implementation
- a universal notification framework
- a full Telegram bot implementation

---

## Final Flow

The agreed and implemented flow is:

1. HTTP request enters the controller
2. controller calls a capture action
3. action creates `Lead` through `Lead::create(...)`
4. `Lead` records `LeadCreated`
5. repository saves `Lead` inside `TransactionManager->run(...)`
6. transaction commits
7. action publishes released events through `EventBus`
8. `LaravelEventBus` dispatches `LeadCreated`
9. `NotifyManagerAboutNewLead` reacts to the event
10. reaction schedules `SendManagerLeadCreatedTelegramJob`
11. queue stores the job
12. worker executes the job
13. job loads payload through `GetManagerLeadNotificationHandler`
14. job calls `TelegramClient`

Important:
- events are published after commit
- queue contains jobs, not domain events
- lead creation does not depend on Telegram delivery success

---

## Final Architectural Decisions

### 1. Domain Events Live In The Aggregate Boundary

Recorded events support is implemented directly in `Lead`.

The constructor stays hydration-safe.

Event recording happens only through:
- `Lead::create(...)`

Repository hydration continues to use the plain constructor.

### 2. `LeadCreated` Stays Minimal

The final payload of `LeadCreated` is:
- `leadId`
- `occurredAt`

It does NOT carry:
- `visitId`
- `visitorId`
- notification text
- UI labels

This keeps the event stable and generic.

### 3. Application Owns The Publishing Abstraction

`EventBus` lives in:
- `src/Inbound/Application/Events/EventBus.php`

Application depends only on this abstraction.

Laravel event system stays in infrastructure.

### 4. Reactions Stay In Application

The first reaction is:
- `NotifyManagerAboutNewLead`

It accepts `LeadCreated` and delegates only to:
- `ManagerLeadNotificationScheduler`

It does NOT:
- send Telegram directly
- dispatch queue jobs directly

### 5. Queue Starts At The Scheduler Layer

`LaravelManagerLeadNotificationScheduler` is the first place where queue
appears in the flow.

It dispatches:
- `SendManagerLeadCreatedTelegramJob`

This preserves the rule:
- event = fact
- reaction = decision
- job = async technical execution

### 6. Notification Payload Uses A Dedicated Read-Side

The final decision was NOT to reuse `GetLeadDetails`.

Instead, the branch introduces a dedicated notification query:
- `GetManagerLeadNotificationQuery`
- `GetManagerLeadNotificationHandler`
- `ManagerLeadNotificationReadModel`
- `ManagerLeadNotificationView`

Reason:
- `GetLeadDetails` is backoffice-heavy
- notification payload should stay lean

The final notification payload contains only:
- `leadId`
- `name`
- `phone`
- `origin`
- `landingUrl`
- `createdAt`

It does NOT carry:
- `visitAttribution`
- `visitorAttribution`
- backoffice summaries

### 7. Telegram Contract Was Split By Layer

Final shape:
- contract lives in `Application`
- implementation lives in `Infrastructure`

Specifically:
- `src/Inbound/Application/Notifications/Telegram/TelegramClient.php`
- `src/Inbound/Application/Notifications/Telegram/TelegramClientException.php`
- `src/Inbound/Infrastructure/Notifications/Telegram/LaravelHttpTelegramClient.php`

This keeps callers independent from the infrastructure namespace.

### 8. First Telegram Iteration Uses Laravel HTTP

The current implementation intentionally uses:
- Laravel HTTP client

It is a minimal outbound-only iteration.

This is deliberate:
- it is enough for manager notifications now
- it keeps the first release simple
- it preserves a clean seam for future replacement

### 9. Explicit Laravel Wiring Was Chosen

Wiring is intentionally explicit in:
- `apps/web/app/Providers/Inbound/CaptureServiceProvider.php`

This provider now:
- binds `EventBus -> LaravelEventBus`
- binds `ManagerLeadNotificationScheduler -> LaravelManagerLeadNotificationScheduler`
- binds `ManagerLeadNotificationReadModel -> EloquentManagerLeadNotificationReadModel`
- binds `TelegramClient -> LaravelHttpTelegramClient`
- registers `LeadCreated -> NotifyManagerAboutNewLead`

The temporary `NullEventBus` was removed.

---

## What Was Implemented

### Domain

Implemented:
- recorded events support in `Lead`
- `LeadCreated`
- `Lead::create(...)`

Covered by tests:
- `Lead::create()` records `LeadCreated`
- plain constructor hydration path records nothing

### Application

Implemented:
- `EventBus`
- `NotifyManagerAboutNewLead`
- `ManagerLeadNotificationScheduler`
- dedicated notification query slice
- event publishing from:
  - `CreateLeadFromFormAction`
  - `CapturePhoneClickAction`

Covered by tests:
- form lead action publishes after successful lead creation
- phone-click action publishes only on lead creation
- reaction calls the scheduler

### Infrastructure / Laravel

Implemented:
- `LaravelEventBus`
- `LaravelManagerLeadNotificationScheduler`
- `SendManagerLeadCreatedTelegramJob`
- `EloquentManagerLeadNotificationReadModel`
- `LaravelHttpTelegramClient`
- explicit service-provider wiring

Covered by tests:
- event bus delivers the event into the reaction path
- scheduler dispatches the queue job
- job loads payload and calls `TelegramClient`
- Telegram client handles success and failure cases

---

## Current Message Scope

The current job sends a plain-text Ukrainian message to the manager.

The message is built from:
- `leadId`
- `createdAt`
- `origin`
- optional `name`
- optional `phone`
- optional `landingUrl`

Formatting is intentionally simple:
- no Markdown/HTML parse mode
- no rich keyboards
- no bot conversation logic

---

## Agreed Future Direction

### 1. Keep The Existing Contract Stable

For the next stage, keep using:
- `TelegramClient`

Do not leak raw HTTP calls into jobs or reactions.

### 2. Short-Term Evolution

After release, if more Telegram API methods are needed, the internals of
`TelegramClient` may move from raw Laravel HTTP to a Telegram SDK.

The surrounding application flow should remain unchanged.

### 3. Longer-Term Evolution

When Telegram becomes a real inbound channel with:
- updates
- commands
- dialogues
- lead capture inside the bot

the infrastructure implementation may move to:
- Nutgram

Again, the goal is to keep the replacement inside the Telegram integration
layer, not in Domain or Application.

### 4. Event Extensibility Model

Future behavior should continue to follow:
- one domain event
- many independent reactions

Good examples:
- `LeadCreated -> NotifyManagerAboutNewLead`
- `LeadCreated -> NotifyClientAboutLeadCreated`
- `LeadCreated -> SyncLeadCreatedToExternalSystem`

Do NOT:
- turn `ManagerLeadNotificationScheduler` into a universal switchboard
- turn `LeadCreated` into a delivery-specific event

---

## Rules For Future Agents

If continuing from this branch:

1. Treat the current event-driven flow as established baseline behavior.
2. Do not move Telegram concerns into controllers, actions, or domain entities.
3. Keep notification payloads read-side driven.
4. Keep reactions narrow and independent.
5. Put async retries at the job layer, not at the domain-event layer.
6. If adding a new channel or recipient, add:
   - a new reaction
   - a new scheduler
   - a new job
   - a new client or client method

Do not redesign this slice unless there is a concrete new business requirement.
