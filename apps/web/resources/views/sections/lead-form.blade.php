<section id="lead-form" class="px-6 pb-20 pt-12 md:pb-24">
    <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)] md:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-teal-700">Заявка</p>
            <h2 class="mt-3 text-3xl font-semibold leading-tight text-slate-900">
                Розрахуємо вартість під ваш запит
            </h2>
            <p class="mt-4 leading-relaxed text-slate-600">
                Заповніть коротку форму. На цьому етапі ми лише збираємо контакт для консультації без складних кроків.
            </p>

            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Передзвін у зручний для вас час
                </li>
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Підбір матеріалу під бюджет
                </li>
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Попередній кошторис до виїзду
                </li>
            </ul>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-[0_20px_40px_-28px_rgba(15,23,42,0.45)] md:p-8">
            @if (session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Ім'я</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        placeholder="Ваше ім'я"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Телефон</label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        value="{{ old('phone') }}"
                        placeholder="+380..."
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                <div>
                    <label for="comment" class="mb-2 block text-sm font-medium text-slate-700">Коментар</label>
                    <textarea
                        id="comment"
                        name="comment"
                        rows="4"
                        placeholder="Коротко опишіть ваш запит"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-100"
                    >{{ old('comment') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-teal-700 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                >
                    Надіслати заявку
                </button>

                <p class="text-xs leading-relaxed text-slate-500">
                    Натискаючи кнопку, ви погоджуєтесь на обробку контактних даних для зворотного зв'язку.
                </p>
            </form>
        </div>
    </div>
</section>
