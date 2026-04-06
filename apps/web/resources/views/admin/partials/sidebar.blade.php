@php
    $currentUser = auth()->user();
    $currentRole = session('backoffice_role_name', 'Користувач');
@endphp

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
            <span
                class="icon-mask h-5 w-5"
                style="--icon-url: url('{{ asset('images/backoffice/close.svg') }}');"
                aria-hidden="true"
            ></span>
        </button>
    </div>

    <nav class="space-y-2">
        <a
            href="{{ route('admin.dashboard') }}"
            class="{{ $activeNav === 'dashboard' ? 'bg-slate-800 text-white' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }} flex h-10 items-center gap-3 overflow-hidden rounded-lg px-2.5 py-2 transition"
            :class="sidebarCollapsed ? 'lg:mx-auto lg:w-10 lg:justify-center lg:px-0' : ''"
            title="Огляд"
        >
            <span
                class="icon-mask h-5 w-5 shrink-0"
                style="--icon-url: url('{{ asset('images/backoffice/dashboard.svg') }}');"
                aria-hidden="true"
            ></span>
            <span x-cloak x-show="showSidebarText()" class="flex-1">Огляд</span>
        </a>

        <a
            href="{{ route('admin.leads.index') }}"
            class="{{ $activeNav === 'leads' ? 'bg-slate-800 text-white' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }} flex h-10 items-center gap-3 overflow-hidden rounded-lg px-2.5 py-2 transition"
            :class="sidebarCollapsed ? 'lg:mx-auto lg:w-10 lg:justify-center lg:px-0' : ''"
            title="Ліди"
        >
            <span
                class="icon-mask h-5 w-5 shrink-0"
                style="--icon-url: url('{{ asset('images/backoffice/leads.svg') }}');"
                aria-hidden="true"
            ></span>
            <span x-cloak x-show="showSidebarText()" class="flex-1">Ліди</span>
        </a>

        <a
            href="{{ route('admin.reports.index') }}"
            class="{{ $activeNav === 'reports' ? 'bg-slate-800 text-white' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }} flex h-10 items-center gap-3 overflow-hidden rounded-lg px-2.5 py-2 transition"
            :class="sidebarCollapsed ? 'lg:mx-auto lg:w-10 lg:justify-center lg:px-0' : ''"
            title="Звіти"
        >
            <span
                class="icon-mask h-5 w-5 shrink-0"
                style="--icon-url: url('{{ asset('images/backoffice/reports.svg') }}');"
                aria-hidden="true"
            ></span>
            <span x-cloak x-show="showSidebarText()" class="flex-1">Звіти</span>
        </a>
    </nav>

    <div class="mt-auto space-y-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex h-auto w-full items-center gap-3 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-3 text-left text-slate-200 transition hover:bg-slate-800 hover:text-white"
                :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                title="Вийти"
            >
                <span
                    class="icon-mask h-5 w-5 shrink-0"
                    style="--icon-url: url('{{ asset('images/backoffice/logout.svg') }}');"
                    aria-hidden="true"
                ></span>

                <span x-cloak x-show="showSidebarText()" class="min-w-0 flex-1 text-sm">
                    <span class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">
                        {{ $currentRole }}
                    </span>
                    <span class="mt-1 block truncate text-sm font-medium text-white">
                        {{ $currentUser?->email }}
                    </span>
                </span>
            </button>
        </form>

        <div x-cloak x-show="showSidebarText()" class="rounded-xl border border-slate-800 bg-slate-950/40 p-4 text-sm text-slate-400">
            Працюйте з бекофісом і на телефоні: меню можна відкрити, закрити або згорнути до режиму з іконками.
        </div>
    </div>
</div>
