<div class="space-y-6">
    <div>
        <div class="text-xl font-bold">Lead Control</div>
        <div class="text-sm text-slate-400">Admin</div>
    </div>

    <nav class="space-y-2">
        <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">
            Dashboard
        </a>

        <a href="{{ route('admin.leads.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">
            Лиды
        </a>
    </nav>
</div>
