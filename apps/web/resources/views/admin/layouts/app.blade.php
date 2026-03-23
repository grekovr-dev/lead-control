<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-slate-900 text-white p-6">
            @include('admin.partials.sidebar')
        </aside>

        <div class="flex-1 min-w-0">
            <header class="bg-white border-b border-slate-200 px-6 py-4">
                @include('admin.partials.header')
            </header>

            <main class="p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-green-100 px-4 py-3 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
