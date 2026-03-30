<div class="flex items-start justify-between gap-4">
    <div class="flex min-w-0 items-start gap-3 lg:gap-4">
        <button
            type="button"
            class="mt-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden"
            @click="openSidebar()"
            aria-label="Відкрити бокову навігацію"
        >
            <span
                class="icon-mask h-5 w-5"
                style="--icon-url: url('{{ asset('images/backoffice/hamburger.svg') }}');"
                aria-hidden="true"
            ></span>
        </button>

        <button
            type="button"
            class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:mt-2 lg:inline-flex"
            @click="toggleDesktopSidebar()"
            :aria-label="sidebarCollapsed ? 'Розгорнути бокову навігацію' : 'Згорнути бокову навігацію'"
        >
            <span
                x-show="!sidebarCollapsed"
                class="icon-mask h-5 w-5"
                style="--icon-url: url('{{ asset('images/backoffice/chevron-left.svg') }}');"
                aria-hidden="true"
            ></span>
            <span
                x-show="sidebarCollapsed"
                x-cloak
                class="icon-mask h-5 w-5"
                style="--icon-url: url('{{ asset('images/backoffice/chevron-right.svg') }}');"
                aria-hidden="true"
            ></span>
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
