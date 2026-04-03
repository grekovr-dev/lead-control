@props([
    'title',
    'description' => null,
    'attribution',
])

<div class="rounded-xl bg-slate-50 px-4 py-4">
    <div class="text-sm font-medium text-slate-900">{{ $title }}</div>

    @if ($description !== null)
        <div class="mt-1 text-sm text-slate-500">{{ $description }}</div>
    @endif

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Джерело</div>
            <div class="mt-1 text-sm text-slate-900">{{ $attribution->source ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Канал</div>
            <div class="mt-1 text-sm text-slate-900">{{ $attribution->medium ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Кампанія</div>
            <div class="mt-1 text-sm text-slate-900">{{ $attribution->campaign ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Оголошення</div>
            <div class="mt-1 text-sm text-slate-900">{{ $attribution->content ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">Ключове слово</div>
            <div class="mt-1 text-sm text-slate-900">{{ $attribution->term ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">GCLID</div>
            <div class="mt-1 break-all text-sm text-slate-900">{{ $attribution->gclid ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">FBCLID</div>
            <div class="mt-1 break-all text-sm text-slate-900">{{ $attribution->fbclid ?? '—' }}</div>
        </div>

        <div class="rounded-lg bg-white px-3 py-3">
            <div class="text-xs uppercase tracking-[0.12em] text-slate-500">MSCLKID</div>
            <div class="mt-1 break-all text-sm text-slate-900">{{ $attribution->msclkid ?? '—' }}</div>
        </div>
    </div>
</div>
