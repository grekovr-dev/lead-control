@extends('admin.layouts.app')

@section('document_title', 'Візити • Lead Control')
@section('page_title', 'Візити')
@section('page_subtitle', 'Детальний список візитів для перевірки візитного рівня воронки та атрибуції.')
@section('active_nav', 'reports')

@section('content')
    @php
        $visibleItemsCount = count($visits->items);
        $firstItem = $visibleItemsCount > 0 ? (($visits->currentPage - 1) * $visits->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <x-admin.reports.screen-layout
        intro-title="Список візитів"
        intro-description="Цей екран показує візити як ціль переходів зі звітів. Він допомагає перевірити, які саме візитні записи стоять за агрегаціями звіту."
        :show-filters="true"
        filters-title="Контекст переходу"
        filters-description="Екран показує нормалізовані параметри переходу, з якими відкрито цей список. Це контекст лише для перегляду, а не самостійна форма пошуку."
        content-title="Візити"
        content-description="Список показує візити у зворотному порядку за останнім дотиком, щоб найактуальніші записи були зверху."
        :show-aside="true"
        aside-title="Усього візитів"
        :aside-heading="$visits->total"
        aside-description="Кількість візитів, що відповідають поточним умовам відбору."
    >
        <x-slot:filters>
            <x-admin.reports.drill-context
                :items="$drillContextItems"
                empty-message="Візити можна відкрити й напряму, але основний сценарій для цього екрана — перехід зі звіту з уже сформованим контекстом."
            />
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
                            <th class="px-4 py-3 font-medium">ID відвідувача</th>
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

                                    <div>{{ $firstAttributionParts !== [] ? implode(' / ', $firstAttributionParts) : 'Без атрибуції' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $visit->firstAttributionCampaign ?? 'Без кампанії' }}</div>
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
