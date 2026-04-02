@extends('admin.layouts.app')

@section('document_title', 'Звіти • Lead Control')
@section('page_title', 'Звіти')
@section('page_subtitle', 'Точка входу в аналітичний розділ бекофісу з готовими звітами та переходами до детальних списків.')
@section('active_nav', 'reports')

@section('content')
    <x-admin.reports.screen-layout
        intro-title="Розділ звітів"
        intro-description="Тут зібрано аналітичні екрани для оцінки воронки, атрибуції та перевірки сирих даних через переходи до кліків, візитів і дотиків."
        :show-filters="false"
        filters-description="Кожен звіт отримає власний delivery-layer request і набір дозволених фільтрів."
        content-title="Доступні звіти"
        content-description="Усі звіти нижче вже доступні в розділі та ведуть до окремих аналітичних екранів."
        :show-aside="true"
        aside-title="Стан розділу"
        aside-heading="5 звітів"
        aside-description="У розділі вже доступні статуси лідів, походження, дві окремі атрибуційні воронки та динаміка воронки."
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

            <a
                href="{{ route('admin.reports.origin-funnel') }}"
                class="block rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50"
            >
                <div>
                    <div>
                        <div class="font-medium text-slate-900">Воронка за походженням</div>
                        <div class="mt-1 text-sm text-slate-500">Зріз по origin з мапованими дотиками, лідами та переходом у список дотиків.</div>
                    </div>
                </div>
            </a>

            <a
                href="{{ route('admin.reports.visit-attribution-funnel') }}"
                class="block rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50"
            >
                <div>
                    <div>
                        <div class="font-medium text-slate-900">Воронка атрибуції візитів</div>
                        <div class="mt-1 text-sm text-slate-500">Візитний зріз: сирі кліки як контекст, візити й ліди як основа конверсії в межах вибраного періоду.</div>
                    </div>
                </div>
            </a>

            <a
                href="{{ route('admin.reports.visitor-acquisition-funnel') }}"
                class="block rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50"
            >
                <div>
                    <div>
                        <div class="font-medium text-slate-900">Воронка залучення відвідувачів</div>
                        <div class="mt-1 text-sm text-slate-500">Когортний зріз за першим візитом: які джерела вперше приводять людей, що згодом стають лідами, навіть якщо лід створено пізніше.</div>
                    </div>
                </div>
            </a>

            <a
                href="{{ route('admin.reports.funnel-trends') }}"
                class="block rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50"
            >
                <div>
                    <div>
                        <div class="font-medium text-slate-900">Динаміка воронки</div>
                        <div class="mt-1 text-sm text-slate-500">Денний зріз кліків, візитів і лідів із акцентом на співвідношення кліків до лідів та конверсію візитів у ліди.</div>
                    </div>
                </div>
            </a>
        </div>
    </x-admin.reports.screen-layout>
@endsection
