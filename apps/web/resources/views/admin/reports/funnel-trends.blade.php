@extends('admin.layouts.app')

@section('document_title', 'Динаміка воронки • Lead Control')
@section('page_title', 'Динаміка воронки')
@section('page_subtitle', 'Денний зріз сирих кліків, візитів і лідів за вибраний період.')
@section('active_nav', 'reports')

@section('content')
    <x-admin.reports.screen-layout
        intro-title="Денний тренд воронки"
        intro-description="Цей звіт показує, як змінюються сирі кліки, візити й ліди день за днем у межах вибраного періоду. Співвідношення кліків до лідів тут є агрегованим показником періоду, а не когортною конверсією одного набору записів."
        :show-filters="true"
        filters-title="Період звіту"
        filters-description="Оберіть готовий пресет або власний період, щоб порівняти денну динаміку воронки за однаковим часовим вікном."
        content-title="Денні показники"
        content-description="Кожен рядок показує окремий день із кількістю кліків, візитів, лідів, співвідношенням кліків до лідів і конверсією візитів у ліди."
        :show-aside="true"
        aside-title="Основний KPI"
        :aside-heading="number_format($report->visitsToLeadsConversionRate, 2, ',', ' ') . '%'"
        aside-description="Конверсія візитів у ліди в межах вибраного періоду. Це головний funnel-орієнтир для цього звіту."
    >
        <x-slot:filters>
            <form
                method="GET"
                action="{{ route('admin.reports.funnel-trends') }}"
                x-data="{ preset: @js($filters['preset']) }"
                class="space-y-4"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end">
                    <div>
                        <label for="funnel-trends-preset" class="block text-sm font-medium text-slate-700">Пресет</label>
                        <div class="relative mt-1">
                            <select
                                id="funnel-trends-preset"
                                name="preset"
                                x-model="preset"
                                class="block w-full min-w-0 appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-11 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 lg:w-56"
                            >
                                @foreach ($presetOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['preset'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>

                            <span
                                aria-hidden="true"
                                class="icon-mask pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-slate-500"
                                style="--icon-url: url('{{ asset('images/backoffice/chevron-right.svg') }}')"
                            ></span>
                        </div>
                    </div>

                    <div
                        x-cloak
                        x-show="preset === 'custom'"
                        class="grid gap-4 sm:grid-cols-2 lg:flex lg:items-end"
                    >
                        <div>
                            <label for="funnel-trends-from" class="block text-sm font-medium text-slate-700">Від</label>
                            <input
                                id="funnel-trends-from"
                                type="date"
                                name="from"
                                value="{{ $filters['from'] }}"
                                @class([
                                    'mt-1 block w-full min-w-0 rounded-lg bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:ring-2 focus:ring-slate-200 lg:w-44',
                                    'border border-red-300 focus:border-red-400' => $errors->has('from'),
                                    'border border-slate-300 focus:border-slate-400' => ! $errors->has('from'),
                                ])
                            >

                            @error('from')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="funnel-trends-to" class="block text-sm font-medium text-slate-700">До</label>
                            <input
                                id="funnel-trends-to"
                                type="date"
                                name="to"
                                value="{{ $filters['to'] }}"
                                @class([
                                    'mt-1 block w-full min-w-0 rounded-lg bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:ring-2 focus:ring-slate-200 lg:w-44',
                                    'border border-red-300 focus:border-red-400' => $errors->has('to'),
                                    'border border-slate-300 focus:border-slate-400' => ! $errors->has('to'),
                                ])
                            >

                            @error('to')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 lg:self-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            Застосувати
                        </button>

                        <a
                            href="{{ route('admin.reports.funnel-trends') }}"
                            class="text-sm font-medium text-slate-600 transition hover:text-slate-900"
                        >
                            Скинути
                        </a>
                    </div>
                </div>

                @error('range')
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror
            </form>
        </x-slot:filters>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Сирі кліки</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->clicksCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Візити</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->visitsCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Ліди</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->leadsCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Співвідношення кліків до лідів</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($report->clicksToLeadsConversionRate, 2, ',', ' ') }}%</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Конверсія візитів у ліди</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($report->visitsToLeadsConversionRate, 2, ',', ' ') }}%</div>
            </div>
        </div>

        @if ($report->rows === [])
            <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Для динаміки воронки поки немає даних.
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Дата</th>
                            <th class="px-4 py-3 font-medium">Кліки</th>
                            <th class="px-4 py-3 font-medium">Візити</th>
                            <th class="px-4 py-3 font-medium">Ліди</th>
                            <th class="px-4 py-3 font-medium">Співвідношення кліків до лідів</th>
                            <th class="px-4 py-3 font-medium">Конверсія візитів у ліди</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report->rows as $row)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-slate-900">{{ $row->date->format('d.m.Y') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->clicksCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->visitsCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->leadsCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($row->clicksToLeadsConversionRate, 2, ',', ' ') }}%</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($row->visitsToLeadsConversionRate, 2, ',', ' ') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.reports.screen-layout>
@endsection
