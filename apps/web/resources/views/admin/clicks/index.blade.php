@extends('admin.layouts.app')

@section('document_title', 'Кліки • Lead Control')
@section('page_title', 'Кліки')
@section('page_subtitle', 'Детальний список кліків для перевірки сирих подій верхнього рівня воронки.')
@section('active_nav', 'reports')

@section('content')
    @php
        $visibleItemsCount = count($clicks->items);
        $firstItem = $visibleItemsCount > 0 ? (($clicks->currentPage - 1) * $clicks->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <x-admin.reports.screen-layout
        intro-title="Список кліків"
        intro-description="Цей екран показує сирі кліки як ціль переходів зі звітів. Він не замінює сам звіт, а допомагає перевірити записи, що стоять за його метриками."
        :show-filters="true"
        filters-title="Контекст переходу"
        filters-description="Екран показує нормалізовані параметри переходу, з якими відкрито цей список. Це контекст лише для перегляду, а не окрема форма пошуку."
        content-title="Кліки"
        content-description="Список показує кліки у зворотному хронологічному порядку з базовим атрибуційним контекстом."
        :show-aside="true"
        aside-title="Усього кліків"
        :aside-heading="$clicks->total"
        aside-description="Кількість записів, що відповідають поточному набору фільтрів."
    >
        <x-slot:filters>
            <x-admin.reports.drill-context
                :items="$drillContextItems"
                empty-message="Кліки можна відкрити й напряму, але основний сценарій для цього екрана — перехід зі звіту з уже сформованим контекстом."
            />
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
                            <th class="px-4 py-3 font-medium">ID відвідувача</th>
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
