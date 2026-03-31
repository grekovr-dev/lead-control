@extends('admin.layouts.app')

@section('document_title', 'Дотики • Lead Control')
@section('page_title', 'Дотики')
@section('page_subtitle', 'Drill-down список дотиків для перевірки подій усередині візитів.')
@section('active_nav', 'reports')

@section('content')
    @php
        $visibleItemsCount = count($touches->items);
        $firstItem = $visibleItemsCount > 0 ? (($touches->currentPage - 1) * $touches->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <x-admin.reports.screen-layout
        intro-title="Список дотиків"
        intro-description="Цей екран показує події всередині візитів і служить ціллю для майбутнього drill-down із funnel-звітів. Він допомагає перевірити конкретні дії, які стоять за агрегованими показниками."
        :show-filters="true"
        filters-title="Фільтри дотиків"
        filters-description="Фільтруйте записи за visit ID, visitor ID та типом дотику. Надалі ці параметри стануть основою для report → drill переходів."
        content-title="Дотики"
        content-description="Список показує дотики у зворотному хронологічному порядку, щоб найновіші взаємодії були зверху."
        :show-aside="true"
        aside-title="Усього дотиків"
        :aside-heading="$touches->total"
        aside-description="Кількість подій дотику, що відповідають поточним умовам відбору."
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('admin.touches.index') }}" class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Поточний набір</h3>
                        <p class="mt-1 text-sm text-slate-500">Фільтри застосовуються до конкретних touch events і зберігаються в query string для майбутнього drill-down контракту.</p>
                    </div>

                    <a
                        href="{{ route('admin.touches.index') }}"
                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Скинути
                    </a>
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_10rem_auto]">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Visit ID</span>
                        <input
                            type="text"
                            name="visitId"
                            value="{{ $filters['visitId'] ?? '' }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Наприклад, visit-123"
                        >
                    </label>

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
                        <span class="mb-2 block text-sm font-medium text-slate-700">Тип дотику</span>
                        <select
                            name="type"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="">Усі типи</option>
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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

        @if ($touches->items === [])
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Дотиків за поточними фільтрами не знайдено.
            </div>
        @else
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Показано {{ $firstItem }}–{{ $lastItem }} із {{ $touches->total }}.</p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Дотик</th>
                            <th class="px-4 py-3 font-medium">Visit ID</th>
                            <th class="px-4 py-3 font-medium">Visitor ID</th>
                            <th class="px-4 py-3 font-medium">Тип</th>
                            <th class="px-4 py-3 font-medium">Сталося</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($touches->items as $touch)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $touch->touchId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="max-w-48 wrap-break-word">{{ $touch->visitId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="max-w-48 wrap-break-word">{{ $touch->visitorId }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div>{{ $touch->typeLabel }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $touch->type }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $touch->occurredAt->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($touches->lastPage > 1)
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <div class="text-sm text-slate-500">Сторінка {{ $touches->currentPage }} із {{ $touches->lastPage }}</div>

                    <div class="flex items-center gap-2">
                        @if ($touches->currentPage > 1)
                            <a
                                href="{{ route('admin.touches.index', array_merge($paginationQuery, ['page' => $touches->currentPage - 1])) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Назад
                            </a>
                        @endif

                        @if ($touches->currentPage < $touches->lastPage)
                            <a
                                href="{{ route('admin.touches.index', array_merge($paginationQuery, ['page' => $touches->currentPage + 1])) }}"
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
