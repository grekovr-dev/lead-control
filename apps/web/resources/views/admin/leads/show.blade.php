@extends('admin.layouts.app')

@section('document_title', 'Лід '.$details->lead->leadId.' • Lead Control')
@section('page_title', 'Лід '.$details->lead->leadId)
@section('page_subtitle', 'Центральний операційний екран для роботи з лідом, атрибуцією, візитом і хронологією подій.')
@section('active_nav', 'leads')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('admin.leads.index') }}"
                data-lead-details-back-button
                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Назад
            </a>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ $details->lead->statusLabel }}</span>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-700">{{ $details->lead->originLabel }}</span>
            </div>
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(22rem,0.85fr)]">
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-sm text-slate-500">Контакт ліда</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $details->lead->name ?? 'Без імені' }}</div>
                            <div class="mt-2 text-base text-slate-600">{{ $details->lead->phone ?? 'Телефон не вказано' }}</div>
                        </div>

                        <div class="rounded-xl bg-slate-50 px-4 py-3 text-right">
                            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Створено</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">{{ $details->lead->createdAt->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">ID ліда</div>
                            <div class="mt-1 break-all text-sm font-medium text-slate-900">{{ $details->lead->leadId }}</div>
                        </div>

                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">ID відвідувача</div>
                            <div class="mt-1 break-all text-sm font-medium text-slate-900">{{ $details->lead->visitorId ?? '—' }}</div>
                        </div>

                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">ID візиту</div>
                            <div class="mt-1 break-all text-sm font-medium text-slate-900">{{ $details->lead->visitId ?? '—' }}</div>
                        </div>

                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Походження</div>
                            <div class="mt-1 text-sm font-medium text-slate-900">{{ $details->lead->originLabel }}</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Атрибуційний контекст ліда</h2>
                            <p class="mt-1 text-sm text-slate-500">Лід тепер зберігає окремо атрибуцію візиту, у межах якого відбулася конверсія, та атрибуцію першого візиту цього відвідувача.</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        @include('admin.leads.partials.attribution-snapshot', [
                            'title' => 'Атрибуція візиту ліда',
                            'description' => 'Саме цей контекст зараз використовується в операційному списку лідів і session-level funnel-звітах.',
                            'attribution' => $details->lead->visitAttribution,
                        ])

                        @include('admin.leads.partials.attribution-snapshot', [
                            'title' => 'Атрибуція першого візиту відвідувача',
                            'description' => 'Це контекст першого джерела, яке привело цього відвідувача в систему.',
                            'attribution' => $details->lead->visitorAttribution,
                        ])
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Таймлайн</h2>
                            <p class="mt-1 text-sm text-slate-500">Повна хронологія кліків, дотиків, змін статусу та нотаток по цьому ліду.</p>
                        </div>
                    </div>

                    @if ($timeline->events === [])
                        <div class="mt-4 rounded-lg border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                            Для цього ліда ще немає подій у таймлайні.
                        </div>
                    @else
                        <div class="mt-4 space-y-4">
                            @foreach ($timeline->events as $event)
                                <div @class([
                                    'rounded-xl border px-4 py-4',
                                    'border-slate-200 bg-slate-50/60' => $event->type !== 'revisit',
                                    'border-teal-200 bg-teal-50/60' => $event->type === 'revisit',
                                ])>
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $event->title }}</div>
                                            @if ($event->description !== null)
                                                <div class="mt-1 wrap-break-word text-sm text-slate-600">{{ $event->description }}</div>
                                            @endif
                                        </div>

                                        <div class="shrink-0 text-xs font-medium text-slate-500">
                                            {{ $event->occurredAt->format('d.m.Y H:i') }}
                                        </div>
                                    </div>

                                    @if ($event->ruleKey !== null || $event->touchTypeLabel !== null || $event->landingUrl !== null || $event->referrer !== null || $event->authorId !== null)
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                            @if ($event->type === 'revisit')
                                                <span class="rounded-full bg-white px-2.5 py-1 text-teal-700">Повторний візит</span>
                                            @endif

                                            @if ($event->touchTypeLabel !== null)
                                                <span class="rounded-full bg-white px-2.5 py-1">{{ $event->touchTypeLabel }}</span>
                                            @endif

                                            @if ($event->ruleKey !== null)
                                                <span class="rounded-full bg-white px-2.5 py-1">Правило: {{ $event->ruleKey }}</span>
                                            @endif

                                            @if ($event->authorId !== null)
                                                <span class="rounded-full bg-white px-2.5 py-1">
                                                    Автор: {{ $event->authorLabel ?? ('#'.$event->authorId) }}
                                                </span>
                                            @endif

                                            @if ($event->landingUrl !== null)
                                                <span class="break-all rounded-full bg-white px-2.5 py-1">Лендинг: {{ $event->landingUrl }}</span>
                                            @endif

                                            @if ($event->referrer !== null)
                                                <span class="break-all rounded-full bg-white px-2.5 py-1">Джерело переходу: {{ $event->referrer }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <div class="space-y-6">
                <section id="lead-status-form" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Оновити статус</h2>
                        <p class="mt-1 text-sm text-slate-500">Ручна зміна поточного статусу ліда з фіксацією переходу в історії.</p>
                    </div>

                    @backofficeCan('leads.status.update')
                        <form method="POST" action="{{ route('admin.leads.status.update', ['leadId' => $details->lead->leadId]) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')

                            <label class="block">
                                <span class="mb-2 block text-sm font-medium text-slate-700">Новий статус</span>
                                <select
                                    name="status"
                                    @if ($errors->has('status')) autofocus @endif
                                    @class([
                                        'block w-full rounded-lg bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none',
                                        'border-red-300 focus:border-red-500' => $errors->has('status'),
                                        'border-slate-200 focus:border-teal-500' => !$errors->has('status'),
                                    ])
                                >
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $details->lead->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            @if (session('success') && session('success_context') === 'lead_status')
                                <div class="rounded-lg bg-green-100 px-3 py-2 text-sm text-green-800">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @error('status')
                                <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                >
                                    Зберегти статус
                                </button>
                            </div>
                        </form>
                    @elsebackofficeCan
                        <div class="mt-4 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            У вас немає прав на зміну статусу цього ліда.
                        </div>
                    @endbackofficeCan
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Операційний зріз</h2>
                        <p class="mt-1 text-sm text-slate-500">Ключові сигнали для швидкого розуміння поточного стану ліда.</p>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-3">
                            <div>
                                <div class="text-sm font-medium text-slate-900">Статус</div>
                                <div class="mt-1 text-sm text-slate-500">Поточний етап обробки ліда.</div>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-sm font-medium text-slate-700">{{ $details->lead->statusLabel }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-3">
                            <div>
                                <div class="text-sm font-medium text-slate-900">Походження</div>
                                <div class="mt-1 text-sm text-slate-500">Канал, через який був створений лід.</div>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-sm font-medium text-slate-700">{{ $details->lead->originLabel }}</span>
                        </div>

                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">Дотики до створення ліда</div>
                                    <div class="mt-1 text-sm text-slate-500">Усі взаємодії у візиті до моменту конверсії.</div>
                                </div>

                                <div class="text-xl font-semibold text-slate-900">{{ $details->preLeadTouchSummary->count }}</div>
                            </div>

                            <div class="mt-3 text-sm text-slate-500">
                                Останній дотик: {{ $details->preLeadTouchSummary->lastOccurredAt?->format('d.m.Y H:i') ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-slate-50 px-3 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">Кліки відвідувача до створення ліда</div>
                                    <div class="mt-1 text-sm text-slate-500">Кліки того самого відвідувача до появи цього ліда.</div>
                                </div>

                                <div class="text-xl font-semibold text-slate-900">{{ $details->preLeadVisitorClickSummary->count }}</div>
                            </div>

                            <div class="mt-3 text-sm text-slate-500">
                                Останній клік: {{ $details->preLeadVisitorClickSummary->lastOccurredAt?->format('d.m.Y H:i') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </section>

                <section id="lead-note-form" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Додати нотатку</h2>
                        <p class="mt-1 text-sm text-slate-500">Короткий операційний контекст, який залишиться в таймлайні цього ліда.</p>
                    </div>

                    @backofficeCan('leads.note.create')
                        <form method="POST" action="{{ route('admin.leads.notes.store', ['leadId' => $details->lead->leadId]) }}" class="mt-4 space-y-3">
                            @csrf

                            <label class="block">
                                <span class="mb-2 block text-sm font-medium text-slate-700">Текст нотатки</span>
                                <textarea
                                    name="note"
                                    rows="5"
                                    @if ($errors->has('note')) autofocus @endif
                                    @class([
                                        'block w-full rounded-lg bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:outline-none',
                                        'border-red-300 focus:border-red-500' => $errors->has('note'),
                                        'border-slate-200 focus:border-teal-500' => !$errors->has('note'),
                                    ])
                                    placeholder="Наприклад, уточнити бюджет або передзвонити після 18:00"
                                >{{ old('note') }}</textarea>
                            </label>

                            @if (session('success') && session('success_context') === 'lead_note')
                                <div class="rounded-lg bg-green-100 px-3 py-2 text-sm text-green-800">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @error('note')
                                <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                >
                                    Додати нотатку
                                </button>
                            </div>
                        </form>
                    @elsebackofficeCan
                        <div class="mt-4 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            У вас немає прав на додавання нотаток до цього ліда.
                        </div>
                    @endbackofficeCan
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Пов’язаний візит</h2>
                        <p class="mt-1 text-sm text-slate-500">Сеанс, у межах якого відбулися ключові взаємодії перед створенням ліда.</p>
                    </div>

                    @if ($details->visit === null)
                        <div class="mt-4 rounded-lg border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                            Для цього ліда не знайдено пов’язаного візиту.
                        </div>
                    @else
                        <div class="mt-4 space-y-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg bg-slate-50 px-3 py-3">
                                    <div class="text-xs uppercase tracking-[0.12em] text-slate-500">ID візиту</div>
                                    <div class="mt-1 break-all text-sm text-slate-900">{{ $details->visit->visitId }}</div>
                                </div>

                                <div class="rounded-lg bg-slate-50 px-3 py-3">
                                    <div class="text-xs uppercase tracking-[0.12em] text-slate-500">ID відвідувача</div>
                                    <div class="mt-1 break-all text-sm text-slate-900">{{ $details->visit->visitorId }}</div>
                                </div>

                                <div class="rounded-lg bg-slate-50 px-3 py-3">
                                    <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Початок візиту</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $details->visit->startedAt->format('d.m.Y H:i') }}</div>
                                </div>

                                <div class="rounded-lg bg-slate-50 px-3 py-3">
                                    <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Остання взаємодія</div>
                                    <div class="mt-1 text-sm text-slate-900">{{ $details->visit->lastTouchedAt->format('d.m.Y H:i') }}</div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg bg-slate-50 px-3 py-3">
                                    <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Перша атрибуція візиту</div>
                                    <div class="mt-3 space-y-2 text-sm text-slate-900">
                                        <div>Джерело: {{ $details->visit->firstAttribution->source ?? '—' }}</div>
                                        <div>Канал: {{ $details->visit->firstAttribution->medium ?? '—' }}</div>
                                        <div>Кампанія: {{ $details->visit->firstAttribution->campaign ?? '—' }}</div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-slate-50 px-3 py-3">
                                    <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Остання атрибуція візиту</div>
                                    <div class="mt-3 space-y-2 text-sm text-slate-900">
                                        <div>Джерело: {{ $details->visit->lastAttribution->source ?? '—' }}</div>
                                        <div>Канал: {{ $details->visit->lastAttribution->medium ?? '—' }}</div>
                                        <div>Кампанія: {{ $details->visit->lastAttribution->campaign ?? '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        </section>
    </div>
@endsection
