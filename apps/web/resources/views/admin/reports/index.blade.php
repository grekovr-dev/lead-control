@extends('admin.layouts.app')

@section('document_title', 'Звіти • Lead Control')
@section('page_title', 'Звіти')
@section('page_subtitle', 'Точка входу в аналітичний розділ бекофісу з майбутніми звітами та drill-down екранами.')
@section('active_nav', 'reports')

@section('content')
    <x-admin.reports.screen-layout
        intro-title="Розділ звітів"
        intro-description="Тут збиратимуться аналітичні екрани для оцінки воронки, атрибуції та розшифровки даних через переходи до кліків, візитів і дотиків."
        :show-filters="false"
        filters-description="Кожен звіт отримає власний delivery-layer request і набір дозволених фільтрів."
        content-title="Заплановані звіти"
        content-description="Поки що це навігаційні заглушки, які фіксують майбутню структуру reporting section."
        :show-aside="true"
        aside-title="Стан розділу"
        aside-heading="Підготовка"
        aside-description="Перший reporting slice буде додано наступними кроками."
    >
        <div class="grid gap-4 md:grid-cols-2">
            <a
                href="{{ route('admin.reports.lead-status') }}"
                class="block rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50"
            >
                <div>
                    <div>
                        <div class="font-medium text-slate-900">Статуси лідів</div>
                        <div class="mt-1 text-sm text-slate-500">Розподіл поточного стану лідів без переходу в історію змін.</div>
                    </div>
                </div>
            </a>

            @foreach ([
                'Воронка за походженням' => 'Зріз по origin з майбутнім drill-down у події.',
                'Воронка за атрибуцією' => 'First-touch acquisition funnel на базі вже погодженої семантики.',
                'Динаміка воронки' => 'Тренди за періодами для основних етапів funnel.',
            ] as $title => $description)
                <a
                    href="{{ route('admin.reports.index') }}"
                    aria-disabled="true"
                    class="pointer-events-none block rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-slate-900">{{ $title }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $description }}</div>
                        </div>

                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-500">Незабаром</span>
                    </div>
                </a>
            @endforeach
        </div>
    </x-admin.reports.screen-layout>
@endsection
