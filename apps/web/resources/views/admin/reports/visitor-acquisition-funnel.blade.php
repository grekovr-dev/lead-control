@extends('admin.layouts.app')

@section('document_title', 'Воронка залучення відвідувачів • Lead Control')
@section('page_title', 'Воронка залучення відвідувачів')
@section('page_subtitle', 'Когортний зріз за першим візитом: нові відвідувачі та ліди, що згодом були створені цими людьми.')
@section('active_nav', 'reports')

@section('content')
    <x-admin.reports.screen-layout
        intro-title="Когорти першого залучення"
        intro-description="Цей звіт відповідає на питання, які джерела вперше приводять людей, що згодом стають лідами. На відміну від воронки атрибуції візитів, тут аналізуються саме відвідувачі за їхнім першим візитом, а не всі візити вибраного періоду."
        :show-filters="true"
        filters-title="Період першого візиту"
        filters-description="Оберіть готовий пресет або власний період, щоб побачити відвідувачів, чий перший візит почався в цей проміжок часу, і ліди, що згодом були створені цими ж людьми."
        content-title="Bucket'и залучення відвідувачів"
        content-description="Кожен рядок показує visitor-attribution bucket із кількістю нових відвідувачів, кількістю лідів цих відвідувачів і конверсією відвідувачів у ліди. Це когортний звіт першого залучення, а не зріз усіх візитів."
        :show-aside="true"
        aside-title="Основний KPI"
        :aside-heading="number_format($report->visitorsToLeadsConversionRate, 2, ',', ' ') . '%'"
        aside-description="Конверсія відвідувачів у ліди для cohort першого візиту в межах вибраного періоду."
    >
        <x-slot:filters>
            <form
                method="GET"
                action="{{ route('admin.reports.visitor-acquisition-funnel') }}"
                x-data="{ preset: @js($filters['preset']) }"
                class="space-y-4"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end">
                    <div>
                        <label for="visitor-acquisition-preset" class="block text-sm font-medium text-slate-700">Пресет</label>
                        <div class="relative mt-1">
                            <select
                                id="visitor-acquisition-preset"
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
                            <label for="visitor-acquisition-from" class="block text-sm font-medium text-slate-700">Від</label>
                            <input
                                id="visitor-acquisition-from"
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
                            <label for="visitor-acquisition-to" class="block text-sm font-medium text-slate-700">До</label>
                            <input
                                id="visitor-acquisition-to"
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
                            href="{{ route('admin.reports.visitor-acquisition-funnel') }}"
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

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Нові відвідувачі</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->visitorsCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Ліди</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $report->leadsCount }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm text-slate-500">Конверсія відвідувачів у ліди</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($report->visitorsToLeadsConversionRate, 2, ',', ' ') }}%</div>
            </div>
        </div>

        @if ($report->rows === [])
            <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Для воронки залучення відвідувачів поки немає даних.
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Джерело</th>
                            <th class="px-4 py-3 font-medium">Канал</th>
                            <th class="px-4 py-3 font-medium">Кампанія</th>
                            <th class="px-4 py-3 font-medium">Нові відвідувачі</th>
                            <th class="px-4 py-3 font-medium">Ліди</th>
                            <th class="px-4 py-3 font-medium">Конверсія відвідувачів у ліди</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report->rows as $row)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-slate-900">{{ $row->visitorAttributionSource ?? 'Без атрибуції' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->visitorAttributionMedium ?? 'Без атрибуції' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->visitorAttributionCampaign ?? 'Без кампанії' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->visitorsCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->leadsCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($row->visitorsToLeadsConversionRate, 2, ',', ' ') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.reports.screen-layout>
@endsection
