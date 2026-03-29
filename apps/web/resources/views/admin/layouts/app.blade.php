@php
    $documentTitle = trim($__env->yieldContent('document_title'));
    $pageTitle = trim($__env->yieldContent('page_title'));
    $pageSubtitle = trim($__env->yieldContent('page_subtitle'));
    $activeNav = trim($__env->yieldContent('active_nav'));

    $pageTitle = $pageTitle !== '' ? $pageTitle : ($title ?? 'Бекофіс');
    $pageSubtitle = $pageSubtitle !== '' ? $pageSubtitle : 'Операційний інтерфейс для роботи з вхідними лідами.';
    $documentTitle = $documentTitle !== '' ? $documentTitle : sprintf('%s • Lead Control', $pageTitle);
    $activeNav = $activeNav !== '' ? $activeNav : null;
@endphp

<!doctype html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div x-data="adminShell()" x-init="init()" @resize.window="syncViewport()" class="min-h-screen flex">
        <div
            x-cloak
            x-show="sidebarOpen && !isDesktop()"
            x-transition.opacity
            class="fixed inset-0 z-30 bg-slate-950/45 lg:hidden"
            @click="closeSidebar()"
        ></div>

        <aside
            x-cloak
            class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 overflow-hidden bg-slate-900 p-6 text-white shadow-2xl transition-all duration-200 lg:static lg:translate-x-0 lg:shadow-none"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            :style="isDesktop() ? { width: sidebarDesktopWidth() } : {}"
        >
            @include('admin.partials.sidebar', ['activeNav' => $activeNav])
        </aside>

        <div class="flex-1 min-w-0">
            <header class="bg-white border-b border-slate-200 px-6 py-4">
                @include('admin.partials.header', [
                    'pageTitle' => $pageTitle,
                    'pageSubtitle' => $pageSubtitle,
                ])
            </header>

            <main class="p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-green-100 px-4 py-3 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mx-auto w-full max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
