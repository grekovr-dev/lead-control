@extends('admin.layouts.app')

@section('document_title', 'Огляд • Lead Control')
@section('page_title', 'Огляд')
@section('page_subtitle', 'Базовий екран адміністративної панелі з ключовими операційними показниками.')
@section('active_nav', 'dashboard')

@section('content')
    <div class="space-y-8" data-admin-leads-list>
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Кліки</div>
                <div class="mt-2 text-2xl font-bold">{{ $overview->clicksCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Візити</div>
                <div class="mt-2 text-2xl font-bold">{{ $overview->visitsCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Дотики</div>
                <div class="mt-2 text-2xl font-bold">{{ $overview->touchesCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Ліди</div>
                <div class="mt-2 text-2xl font-bold">{{ $overview->leadsCount }}</div>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Кліки → ліди</div>
                <div class="mt-2 text-2xl font-bold">{{ $overview->clicksToLeadsConversionRate }}%</div>
                <p class="mt-2 text-sm text-slate-500">Частка лідів від усіх кліків по лендингу.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Візити → ліди</div>
                <div class="mt-2 text-2xl font-bold">{{ $overview->visitsToLeadsConversionRate }}%</div>
                <p class="mt-2 text-sm text-slate-500">Частка лідів від візитів, які дійшли до взаємодії.</p>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Статуси лідів</h2>

                <div class="mt-4 space-y-3">
                    @foreach ($overview->leadStatusBreakdown as $item)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                            <span class="text-sm text-slate-700">{{ $item->label }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $item->count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Типи дотиків</h2>

                <div class="mt-4 space-y-3">
                    @foreach ($overview->touchTypeBreakdown as $item)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                            <span class="text-sm text-slate-700">{{ $item->label }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $item->count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Походження лідів</h2>

                <div class="mt-4 space-y-3">
                    @forelse ($overview->leadOriginBreakdown as $item)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                            <span class="text-sm text-slate-700">{{ $item->label }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $item->count }}</span>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-200 px-3 py-4 text-sm text-slate-500">
                            Поки що немає даних про походження лідів.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Останні ліди</h2>
                    <p class="mt-1 text-sm text-slate-500">Операційний список останніх конверсій без переходу в окремий розділ.</p>
                </div>

                <a
                    href="{{ route('admin.leads.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Відкрити список лідів
                </a>
            </div>

            @if ($overview->recentLeads === [])
                <div class="mt-4 rounded-lg border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                    Ще немає лідів для відображення.
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Лід</th>
                                <th class="px-4 py-3 font-medium">Контакт</th>
                                <th class="px-4 py-3 font-medium">Статус</th>
                                <th class="px-4 py-3 font-medium">Походження</th>
                                <th class="px-4 py-3 font-medium">Атрибуція</th>
                                <th class="px-4 py-3 font-medium">Створено</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($overview->recentLeads as $lead)
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a
                                                href="{{ route('admin.leads.show', ['leadId' => $lead->leadId]) }}"
                                                data-lead-details-source-link
                                                class="font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-700 hover:decoration-slate-400"
                                                title="{{ $lead->leadId }}"
                                            >
                                                {{ $lead->shortLeadId }}
                                            </a>

                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                                data-copy-lead-id-button
                                                data-copy-value="{{ $lead->leadId }}"
                                                data-copy-label="Скопіювати повний ID ліда"
                                                data-copied-label="Скопійовано"
                                                title="Скопіювати повний ID ліда"
                                                aria-label="Скопіювати повний ID ліда"
                                            >
                                                <span
                                                    class="icon-mask h-4 w-4"
                                                    style="--icon-url: url('{{ asset('images/backoffice/copy.svg') }}');"
                                                    aria-hidden="true"
                                                ></span>
                                            </button>
                                        </div>
                                        @if ($lead->visitId !== null)
                                            <div class="mt-1 text-xs text-slate-500">Візит: {{ $lead->visitId }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div>{{ $lead->name ?? 'Без імені' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $lead->phone ?? 'Телефон не вказано' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $lead->statusLabel }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $lead->originLabel }}</td>
                                    <td class="px-4 py-3 text-slate-700">
                                        @php
                                            $attributionParts = array_filter([$lead->attributionSource, $lead->attributionMedium]);
                                        @endphp

                                        {{ $attributionParts !== [] ? implode(' / ', $attributionParts) : 'Без атрибуції' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $lead->createdAt->format('d.m.Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
