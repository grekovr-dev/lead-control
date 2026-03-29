<div class="flex items-start justify-between gap-4">
    <div class="flex min-w-0 items-start gap-3">
        <button
            type="button"
            class="mt-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden"
            @click="openSidebar()"
            aria-label="Відкрити бокову навігацію"
        >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" d="M3 5h14M3 10h14M3 15h14" />
            </svg>
        </button>

        <button
            type="button"
            class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:mt-2 lg:inline-flex"
            @click="toggleDesktopSidebar()"
            :aria-label="sidebarCollapsed ? 'Розгорнути бокову навігацію' : 'Згорнути бокову навігацію'"
        >
            <svg x-show="!sidebarCollapsed" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5" />
            </svg>
            <svg x-show="sidebarCollapsed" x-cloak class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5 13 10l-5.5 5.5" />
            </svg>
        </button>

        <div class="min-w-0">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-700">
                Бекофіс
            </div>

            <div class="mt-1 text-lg font-semibold text-slate-900">
                {{ $pageTitle }}
            </div>

            <div class="mt-1 text-sm text-slate-500">
                {{ $pageSubtitle }}
            </div>
        </div>
    </div>

    <div class="hidden text-sm text-slate-500 md:block">
        Операційна робота з лідами
    </div>
</div>
