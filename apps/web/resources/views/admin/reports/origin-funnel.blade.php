@extends('admin.layouts.app')

@section('document_title', 'Воронка за походженням • Lead Control')
@section('page_title', 'Воронка за походженням')
@section('page_subtitle', 'Порівняння дотиків і лідів за походженням з переходом у список дотиків для перевірки сирих подій.')
@section('active_nav', 'reports')

@section('content')
    <x-admin.reports.screen-layout
        intro-title="Звіт за походженням"
        intro-description="Цей звіт показує, як маповані походження лідів співвідносяться з дотиками всередині funnel. Перехід у список дотиків доступний для кількості дотиків, щоб можна було перевірити сирі події за кожним походженням."
        :show-filters="false"
        content-title="Зріз за походженням"
        content-description="Кожен рядок показує кількість дотиків, кількість лідів і конверсію дотиків у ліди для одного походження. Значення в колонці дотиків відкриває детальний список дотиків для цього походження."
        :show-aside="true"
        aside-title="Загальна конверсія"
        :aside-heading="number_format($report->touchesToLeadsConversionRate, 2, ',', ' ') . '%'"
        aside-description="Частка лідів від усіх мапованих дотиків у звіті за походженням."
    >
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Усього мапованих дотиків</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->touchesCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Усього лідів</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->leadsCount }}</div>
            </div>
        </div>

        @if ($report->rows === [])
            <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Для звіту за походженням поки немає мапованих даних.
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Походження</th>
                            <th class="px-4 py-3 font-medium">Дотиків</th>
                            <th class="px-4 py-3 font-medium">Лідів</th>
                            <th class="px-4 py-3 font-medium">Конверсія дотиків у ліди</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report->rows as $row)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-slate-900">{{ $row->originLabel }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if ($row->touchDrillType !== null && $row->touchesCount > 0)
                                        <a
                                            href="{{ route('admin.touches.index', ['type' => $row->touchDrillType]) }}"
                                            class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1.5 font-medium text-slate-900 transition hover:border-slate-300 hover:bg-slate-50"
                                        >
                                            {{ $row->touchesCount }}
                                        </a>
                                    @else
                                        {{ $row->touchesCount }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->leadsCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($row->touchesToLeadsConversionRate, 2, ',', ' ') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.reports.screen-layout>
@endsection
