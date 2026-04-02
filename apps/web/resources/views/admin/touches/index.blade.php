@extends('admin.layouts.app')

@section('document_title', 'Дотики • Lead Control')
@section('page_title', 'Дотики')
@section('page_subtitle', 'Детальний список дотиків для перевірки подій усередині візитів.')
@section('active_nav', 'reports')

@section('content')
    @php
        $visibleItemsCount = count($touches->items);
        $firstItem = $visibleItemsCount > 0 ? (($touches->currentPage - 1) * $touches->perPage) + 1 : 0;
        $lastItem = $visibleItemsCount > 0 ? $firstItem + $visibleItemsCount - 1 : 0;
    @endphp

    <x-admin.reports.screen-layout
        intro-title="Список дотиків"
        intro-description="Цей екран показує події всередині візитів і служить ціллю для переходів зі звітів. Він допомагає перевірити конкретні дії, які стоять за агрегованими показниками."
        :show-filters="true"
        filters-title="Контекст переходу"
        filters-description="Екран показує нормалізовані параметри переходу, з якими відкрито цей список. Це контекст лише для перегляду, а не самостійна форма пошуку."
        content-title="Дотики"
        content-description="Список показує дотики у зворотному хронологічному порядку, щоб найновіші взаємодії були зверху."
        :show-aside="true"
        aside-title="Усього дотиків"
        :aside-heading="$touches->total"
        aside-description="Кількість подій дотику, що відповідають поточним умовам відбору."
    >
        <x-slot:filters>
            <x-admin.reports.drill-context
                :items="$drillContextItems"
                empty-message="Дотики можна відкрити й напряму, але основний сценарій для цього екрана — перехід зі звіту з уже сформованим контекстом."
            />
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
                            <th class="px-4 py-3 font-medium">ID візиту</th>
                            <th class="px-4 py-3 font-medium">ID відвідувача</th>
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
