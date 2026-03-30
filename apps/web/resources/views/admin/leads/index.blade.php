@extends('admin.layouts.app')

@section('document_title', 'Ліди • Lead Control')
@section('page_title', 'Ліди')
@section('page_subtitle', 'Поточний операційний список лідів з фільтрами та керованою пагінацією.')
@section('active_nav', 'leads')

@section('content')
    @php
        $visibleItemsCount = count($leads->items);
        $firstItem = $visibleItemsCount > 0 ? (($leads->currentPage - 1) * $leads->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <div class="space-y-6" data-admin-leads-list>
        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Поточний список</h2>
                <p class="mt-2 text-sm text-slate-500">Операційний перелік нових і вже оброблюваних лідів без історії змін на цьому екрані.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Усього лідів</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $leads->total }}</div>
                <div class="mt-2 text-sm text-slate-500">Сторінка {{ $leads->currentPage }} із {{ $leads->lastPage }}</div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Фільтри</h2>
                    <p class="mt-1 text-sm text-slate-500">Швидкі фільтри застосовуються одразу, а пошук за атрибуцією працює через окрему кнопку.</p>
                </div>

                <a
                    href="{{ route('admin.leads.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Скинути
                </a>
            </div>

            <form method="GET" action="{{ route('admin.leads.index') }}" class="mt-4 grid gap-4 lg:grid-cols-3">
                @if ($filters['attributionSource'] !== null)
                    <input type="hidden" name="attributionSource" value="{{ $filters['attributionSource'] }}">
                @endif

                @if ($filters['attributionMedium'] !== null)
                    <input type="hidden" name="attributionMedium" value="{{ $filters['attributionMedium'] }}">
                @endif

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Статус</span>
                    <select
                        name="status"
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none"
                        @change="$el.form.requestSubmit()"
                    >
                        <option value="">Усі статуси</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Походження</span>
                    <select
                        name="origin"
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none"
                        @change="$el.form.requestSubmit()"
                    >
                        <option value="">Усі джерела</option>
                        @foreach ($originOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['origin'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">На сторінці</span>
                    <select
                        name="perPage"
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none"
                        @change="$el.form.requestSubmit()"
                    >
                        @foreach ($perPageOptions as $perPage)
                            <option value="{{ $perPage }}" @selected($filters['perPage'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </label>
            </form>

            <form method="GET" action="{{ route('admin.leads.index') }}" class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                @if ($filters['status'] !== null)
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                @endif

                @if ($filters['origin'] !== null)
                    <input type="hidden" name="origin" value="{{ $filters['origin'] }}">
                @endif

                <input type="hidden" name="perPage" value="{{ $filters['perPage'] }}">

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Джерело атрибуції</span>
                    <input
                        type="text"
                        name="attributionSource"
                        value="{{ $filters['attributionSource'] ?? '' }}"
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:outline-none"
                        placeholder="Наприклад, google"
                    >
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Канал атрибуції</span>
                    <input
                        type="text"
                        name="attributionMedium"
                        value="{{ $filters['attributionMedium'] ?? '' }}"
                        class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:outline-none"
                        placeholder="Наприклад, cpc"
                    >
                </label>

                <div class="flex items-end justify-end gap-2">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                    >
                        Шукати
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Ліди</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($leads->total === 0)
                            Лідів за поточними фільтрами не знайдено.
                        @else
                            Показано {{ $firstItem }}–{{ $lastItem }} із {{ $leads->total }}.
                        @endif
                    </p>
                </div>
            </div>

            @if ($leads->items === [])
                <div class="mt-4 rounded-lg border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                    Ще немає лідів для оперативної роботи.
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
                            @foreach ($leads->items as $lead)
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

                @if ($leads->lastPage > 1)
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <div class="text-sm text-slate-500">Сторінка {{ $leads->currentPage }} із {{ $leads->lastPage }}</div>

                        <div class="flex items-center gap-2">
                            @if ($leads->currentPage > 1)
                                <a
                                    href="{{ route('admin.leads.index', array_merge($paginationQuery, ['page' => $leads->currentPage - 1])) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    Назад
                                </a>
                            @endif

                            @if ($leads->currentPage < $leads->lastPage)
                                <a
                                    href="{{ route('admin.leads.index', array_merge($paginationQuery, ['page' => $leads->currentPage + 1])) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    Далі
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
