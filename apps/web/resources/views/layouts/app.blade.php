<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Lead Control')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto max-w-5xl px-4 py-4 flex items-center justify-between">
            <div class="font-semibold">Lead Control</div>
            <nav class="text-sm text-slate-600 flex gap-4">
                <a class="hover:text-slate-900" href="#features">Функции</a>
                <a class="hover:text-slate-900" href="#price">Цена</a>
                <a class="hover:text-slate-900" href="#contact">Контакт</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10">
        @yield('content')
    </main>

    <footer class="border-t bg-white">
        <div class="mx-auto max-w-5xl px-4 py-6 text-sm text-slate-500">
            © {{ date('Y') }} Lead Control
        </div>
    </footer>
</body>
</html>
