@props([
    'introTitle',
    'introDescription',
    'showFilters' => false,
    'filtersTitle' => 'Фільтри',
    'filtersDescription' => null,
    'contentTitle',
    'contentDescription',
    'showAside' => false,
    'asideTitle' => null,
    'asideHeading' => null,
    'asideDescription' => null,
])

<div class="space-y-6">
    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $introTitle }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $introDescription }}</p>
        </div>

        @if ($showAside)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @if ($asideTitle !== null)
                    <div class="text-sm text-slate-500">{{ $asideTitle }}</div>
                @endif

                @if ($asideHeading !== null)
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ $asideHeading }}</div>
                @endif

                @if ($asideDescription !== null)
                    <div class="mt-2 text-sm text-slate-500">{{ $asideDescription }}</div>
                @endif
            </div>
        @endif
    </section>

    @if ($showFilters)
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $filtersTitle }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $filtersDescription }}</p>
            </div>

            <div class="mt-4">
                @isset($filters)
                    {{ $filters }}
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                        Фільтри цього звіту з’являться тут, коли він отримає окремий request-контракт.
                    </div>
                @endisset
            </div>
        </section>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $contentTitle }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $contentDescription }}</p>
        </div>

        <div class="mt-4">
            {{ $slot }}
        </div>
    </section>
</div>
