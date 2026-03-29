<div class="flex h-full flex-col space-y-6">
    <div class="flex h-12 items-start justify-between gap-3">
        <div class="flex min-h-12 min-w-0 items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-sm font-bold text-white">
                LC
            </div>

            <div x-cloak x-show="showSidebarText()" class="min-w-0 leading-tight">
                <div class="truncate text-xl font-bold leading-none">Lead Control</div>
                <div class="mt-1 text-sm text-slate-400">Бекофіс</div>
            </div>
        </div>

        <button
            type="button"
            class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white lg:hidden"
            @click="closeSidebar()"
            aria-label="Закрити бокову навігацію"
        >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" d="m5 5 10 10M15 5 5 15" />
            </svg>
        </button>
    </div>

    <nav class="space-y-2">
        <a
            href="{{ route('admin.dashboard') }}"
            class="{{ $activeNav === 'dashboard' ? 'bg-slate-800 text-white' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }} flex h-10 items-center gap-3 overflow-hidden rounded-lg px-2.5 py-2 transition"
            :class="sidebarCollapsed ? 'lg:mx-auto lg:w-10 lg:justify-center lg:px-0' : ''"
            title="Огляд"
        >
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10.894 2.553a1 1 0 0 0-1.788 0l-7 14A1 1 0 0 0 3 18h14a1 1 0 0 0 .894-1.447l-7-14ZM11 7a1 1 0 1 0-2 0v3a1 1 0 1 0 2 0V7Zm-1 8a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 10 15Z" />
            </svg>
            <span x-cloak x-show="showSidebarText()" class="flex-1">Огляд</span>
        </a>

        <div
            class="flex h-10 items-center gap-3 overflow-hidden rounded-lg px-2.5 py-2 text-slate-500"
            :class="sidebarCollapsed ? 'lg:mx-auto lg:w-10 lg:justify-center lg:px-0' : ''"
            title="Ліди"
        >
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 3a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7ZM4 15.25C4 13.455 6.686 12 10 12s6 1.455 6 3.25V17H4v-1.75Z" />
            </svg>

            <div x-cloak x-show="showSidebarText()" class="flex min-w-0 flex-1 items-center justify-between gap-2">
                <span>Ліди</span>
                <span class="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                    Незабаром
                </span>
            </div>
        </div>
    </nav>

    <div x-cloak x-show="showSidebarText()" class="mt-auto rounded-xl border border-slate-800 bg-slate-950/40 p-4 text-sm text-slate-400">
        Працюйте з бекофісом і на телефоні: меню можна відкрити, закрити або згорнути до режиму з іконками.
    </div>
</div>
