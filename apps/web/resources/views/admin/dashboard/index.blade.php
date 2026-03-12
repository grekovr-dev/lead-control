@extends('admin.layouts.app')

@section('content')
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Всего лидов</div>
            <div class="mt-2 text-2xl font-bold">{{ $leadsCount }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Новые</div>
            <div class="mt-2 text-2xl font-bold">{{ $newLeadsCount }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Закрытые</div>
            <div class="mt-2 text-2xl font-bold">{{ $wonLeadsCount }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Потерянные</div>
            <div class="mt-2 text-2xl font-bold">{{ $lostLeadsCount }}</div>
        </div>
    </div>
@endsection
