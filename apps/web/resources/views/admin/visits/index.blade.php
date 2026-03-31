@extends('admin.layouts.app')

@section('document_title', 'Візити • Lead Control')
@section('page_title', 'Візити')
@section('page_subtitle', 'Drill-down список візитів для перевірки сесійного рівня воронки та атрибуції.')
@section('active_nav', 'reports')

@section('content')
    @php
        $visibleItemsCount = count($visits->items);
        $firstItem = $visibleItemsCount > 0 ? (($visits->currentPage - 1) * $visits->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <x-admin.reports.screen-layout
        intro-title="Список візитів"
        intro-description="Цей екран показує візити як ціль для майбутнього drill-down із funnel-звітів. Він допомагає перевірити, які саме сесійні записи стоять за агрегаціями звіту."
        :show-filters="true"
        filters-title="Фільтри візитів"
        filters-description="Фільтруйте візити за visitor ID, first-touch та last-touch атрибуцією. Пізніше ці параметри використовуватимуться і для drill-переходів зі звітів."
        content-title="Візити"
        content-description="Список показує візити у зворотному порядку за останнім дотиком, щоб найактуальніші записи були зверху."
        :show-aside="true"
        aside-title="Усього візитів"
        :aside-heading="$visits->total"
        aside-description="Кількість візитів, що відповідають поточним умовам відбору."
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('admin.visits.index') }}" class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Поточний набір</h3>
                        <p class="mt-1 text-sm text-slate-500">Фільтри застосовуються до записів візитів і зберігаються в query string для майбутнього drill-down контракту.</p>
                    </div>

                    <a
                        href="{{ route('admin.visits.index') }}"
                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Скинути
                    </a>
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_10rem_auto]">
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
                        <span class="mb-2 block text-sm font-medium text-slate-700">Перше джерело</span>
                        <input
                            type="text"
                            name="firstAttributionSource"
                            value="{{ $filters['firstAttributionSource'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, google"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Перший канал</span>
                        <input
                            type="text"
                            name="firstAttributionMedium"
                            value="{{ $filters['firstAttributionMedium'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, cpc"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Останнє джерело</span>
                        <input
                            type="text"
                            name="lastAttributionSource"
                            value="{{ $filters['lastAttributionSource'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, google"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Останній канал</span>
                        <input
                            type="text"
                            name="lastAttributionMedium"
                            value="{{ $filters['lastAttributionMedium'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, organic"
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

        @if ($visits->items === [])
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Візитів за поточними фільтрами не знайдено.
            </div>
        @else
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Показано {{ $firstItem }}–{{ $lastItem }} із {{ $visits->total }}.</p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Візит</th>
                            <th class="px-4 py-3 font-medium">Visitor ID</th>
                            <th class="px-4 py-3 font-medium">Перша атрибуція</th>
                            <th class="px-4 py-3 font-medium">Остання атрибуція</th>
                            <th class="px-4 py-3 font-medium">Старт</th>
                            <th class="px-4 py-3 font-medium">Останній дотик</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($visits->items as $visit)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $visit->visitId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="max-w-48 wrap-break-word">{{ $visit->visitorId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    @php
                                        $firstAttributionParts = array_filter([$visit->firstAttributionSource, $visit->firstAttributionMedium]);
                                    @endphp

                                    {{ $firstAttributionParts !== [] ? implode(' / ', $firstAttributionParts) : 'Без атрибуції' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    @php
                                        $lastAttributionParts = array_filter([$visit->lastAttributionSource, $visit->lastAttributionMedium]);
                                    @endphp

                                    {{ $lastAttributionParts !== [] ? implode(' / ', $lastAttributionParts) : 'Без атрибуції' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $visit->startedAt->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $visit->lastTouchedAt->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($visits->lastPage > 1)
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <div class="text-sm text-slate-500">Сторінка {{ $visits->currentPage }} із {{ $visits->lastPage }}</div>

                    <div class="flex items-center gap-2">
                        @if ($visits->currentPage > 1)
                            <a
                                href="{{ route('admin.visits.index', array_merge($paginationQuery, ['page' => $visits->currentPage - 1])) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Назад
                            </a>
                        @endif

                        @if ($visits->currentPage < $visits->lastPage)
                            <a
                                href="{{ route('admin.visits.index', array_merge($paginationQuery, ['page' => $visits->currentPage + 1])) }}"
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
