@props([
    'items' => [],
    'emptyMessage' => 'Екран відкрито без контексту переходу. Основний сценарій для цього списку — перехід зі звіту.',
])

@if ($items === [])
    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
        {{ $emptyMessage }}
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($items as $item)
            <div class="rounded-lg bg-slate-50 px-3 py-3">
                <div class="text-xs uppercase tracking-[0.12em] text-slate-500">{{ $item['label'] }}</div>
                <div class="mt-1 wrap-break-word text-sm text-slate-900">{{ $item['value'] }}</div>
            </div>
        @endforeach
    </div>
@endif
