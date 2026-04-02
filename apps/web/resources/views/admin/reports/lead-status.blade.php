@extends('admin.layouts.app')

@section('document_title', 'Статуси лідів • Lead Control')
@section('page_title', 'Статуси лідів')
@section('page_subtitle', 'Поточний розподіл статусів серед лідів, створених за вибраний період.')
@section('active_nav', 'reports')

@section('content')
    <x-admin.reports.screen-layout
        intro-title="Звіт за статусами лідів"
        intro-description="Цей екран показує, у яких статусах зараз перебувають ліди, створені за вибраний період. Це current-state звіт, а не історія переходів між статусами."
        :show-filters="true"
        filters-title="Період створення лідів"
        filters-description="Оберіть готовий пресет або власний період, щоб побачити поточний розподіл статусів серед лідів, створених у цей проміжок часу."
        content-title="Розподіл за статусами"
        content-description="Звіт показує поточну картину за статусами, а не кількість переходів у статуси за період."
        :show-aside="true"
        aside-title="Усього лідів"
        :aside-heading="$report->leadsCount"
        aside-description="Кількість поточних лідів, створених у межах вибраного періоду."
    >
        <x-slot:filters>
            <form
                method="GET"
                action="{{ route('admin.reports.lead-status') }}"
                x-data="{ preset: @js($filters['preset']) }"
                class="space-y-4"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end">
                    <div>
                        <label for="lead-status-preset" class="block text-sm font-medium text-slate-700">Пресет</label>
                        <div class="relative mt-1">
                            <select
                                id="lead-status-preset"
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
                            <label for="lead-status-from" class="block text-sm font-medium text-slate-700">Від</label>
                            <input
                                id="lead-status-from"
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
                            <label for="lead-status-to" class="block text-sm font-medium text-slate-700">До</label>
                            <input
                                id="lead-status-to"
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
                            href="{{ route('admin.reports.lead-status') }}"
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

        @if ($report->rows === [])
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                Ще немає даних для відображення цього звіту.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Статус</th>
                            <th class="px-4 py-3 font-medium">Лідів</th>
                            <th class="px-4 py-3 font-medium">Частка від загальної кількості</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($report->rows as $row)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-slate-900">{{ $row->statusLabel }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->leadsCount }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($row->shareOfTotalRate, 2, ',', ' ') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.reports.screen-layout>
@endsection
