<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вхід • Lead Control</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900">
    <div class="grid min-h-screen lg:grid-cols-2">
        <section class="relative flex items-center overflow-hidden bg-slate-950 px-6 py-16 text-white sm:px-10 lg:px-16">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(45,212,191,0.18),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.16),transparent_35%)]"></div>

            <div class="relative max-w-xl">
                <div class="inline-flex rounded-full border border-teal-400/30 bg-teal-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-teal-200">
                    Lead Control
                </div>

                <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl">
                    Вхід у бекофіс
                </h1>

                <p class="mt-5 max-w-lg text-base leading-7 text-slate-300">
                    Увійдіть, щоб переглядати воронку, керувати лідами, додавати нотатки та працювати з операційним бекофісом.
                </p>
            </div>
        </section>

        <section class="flex items-center justify-center bg-slate-100 px-6 py-16 sm:px-10 lg:px-16">
            <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
                <div class="mb-8">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">
                        Авторизація
                    </div>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                        Увійдіть у бекофіс
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Використайте службовий акаунт адміністратора або менеджера.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">Не вдалося виконати вхід.</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">
                            Електронна пошта
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
                            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                            placeholder="admin@example.test"
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">
                            Пароль
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                            placeholder="••••••••"
                        >
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-3 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        >
                        Запам’ятати мене
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800"
                    >
                        Увійти
                    </button>
                </form>

                <div class="mt-6 border-t border-slate-200 pt-5 text-sm text-slate-500">
                    <a href="{{ route('landing') }}" class="font-medium text-teal-700 transition hover:text-teal-800">
                        Повернутися на лендінг
                    </a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
