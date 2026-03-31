@extends('admin.layouts.app')

@section('document_title', 'Кліки • Lead Control')
@section('page_title', 'Кліки')
@section('page_subtitle', 'Drill-down список кліків для перевірки сирих подій верхнього рівня воронки.')
@section('active_nav', 'reports')

@section('content')
    @php
        $visibleItemsCount = count($clicks->items);
        $firstItem = $visibleItemsCount > 0 ? (($clicks->currentPage - 1) * $clicks->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <x-admin.reports.screen-layout
        intro-title="Список кліків"
        intro-description="Цей екран показує сирі кліки, які можуть бути ціллю drill-down переходів із funnel-звітів. Він не замінює сам звіт, а допомагає перевірити записи, що стоять за його метриками."
        :show-filters="true"
        filters-title="Фільтри кліків"
        filters-description="Фільтруйте перелік за visitor ID та атрибуцією. Пізніше ці самі параметри прийматимуть drill-переходи зі звітів."
        content-title="Кліки"
        content-description="Список показує кліки у зворотному хронологічному порядку з базовим атрибуційним контекстом."
        :show-aside="true"
        aside-title="Усього кліків"
        :aside-heading="$clicks->total"
        aside-description="Кількість записів, що відповідають поточному набору фільтрів."
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('admin.clicks.index') }}" class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Поточний набір</h3>
                        <p class="mt-1 text-sm text-slate-500">Фільтри застосовуються до сирих подій кліку та зберігаються в query string для майбутніх drill-переходів.</p>
                    </div>

                    <a
                        href="{{ route('admin.clicks.index') }}"
                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Скинути
                    </a>
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_10rem_auto]">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Visitor ID</span>
                        <input
                            type="text"
                            name="visitorId"
                            value="{{ $filters['visitorId'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, visitor-123"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Джерело атрибуції</span>
                        <input
                            type="text"
                            name="attributionSource"
                            value="{{ $filters['attributionSource'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, google"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Канал атрибуції</span>
                        <input
                            type="text"
                            name="attributionMedium"
                            value="{{ $filters['attributionMedium'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, cpc"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Кампанія</span>
                        <input
                            type="text"
                            name="attributionCampaign"
                            value="{{ $filters['attributionCampaign'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, spring-sale"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">На сторінці</span>
                        <select
                            name="perPage"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                            @foreach ($perPageOptions as $perPage)
                                <option value="{{ $perPage }}" @selected($filters['perPage'] === $perPage)>{{ $perPage }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            Застосувати
                        </button>
                    </div>
                </div>
            </form>
        </x-slot:filters>

        @if ($clicks->items === [])
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Кліків за поточними фільтрами не знайдено.
            </div>
        @else
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Показано {{ $firstItem }}–{{ $lastItem }} із {{ $clicks->total }}.</p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Клік</th>
                            <th class="px-4 py-3 font-medium">Visitor ID</th>
                            <th class="px-4 py-3 font-medium">Landing / referrer</th>
                            <th class="px-4 py-3 font-medium">Атрибуція</th>
                            <th class="px-4 py-3 font-medium">Сталося</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($clicks->items as $click)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $click->clickId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="max-w-48 wrap-break-word">{{ $click->visitorId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="max-w-md wrap-break-word">{{ $click->landingUrl }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $click->referrer ?? 'Без referrer' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    @php
                                        $attributionParts = array_filter([$click->attributionSource, $click->attributionMedium]);
                                    @endphp

                                    <div>{{ $attributionParts !== [] ? implode(' / ', $attributionParts) : 'Без атрибуції' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $click->attributionCampaign ?? 'Без кампанії' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $click->occurredAt->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($clicks->lastPage > 1)
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <div class="text-sm text-slate-500">Сторінка {{ $clicks->currentPage }} із {{ $clicks->lastPage }}</div>

                    <div class="flex items-center gap-2">
                        @if ($clicks->currentPage > 1)
                            <a
                                href="{{ route('admin.clicks.index', array_merge($paginationQuery, ['page' => $clicks->currentPage - 1])) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Назад
                            </a>
                        @endif

                        @if ($clicks->currentPage < $clicks->lastPage)
                            <a
                                href="{{ route('admin.clicks.index', array_merge($paginationQuery, ['page' => $clicks->currentPage + 1])) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Далі
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-admin.reports.screen-layout>
@endsection
