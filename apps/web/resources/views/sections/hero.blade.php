<section class="relative overflow-hidden px-6 pb-16 pt-14 md:pb-20 md:pt-20">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-80 bg-linear-to-b from-teal-50 via-cyan-50/60 to-transparent"></div>
    <div class="pointer-events-none absolute -right-14 -top-20 -z-10 h-64 w-64 rounded-full bg-teal-200/40 blur-3xl"></div>

    <div class="mx-auto grid max-w-6xl gap-10 md:grid-cols-[1.05fr_0.95fr] md:items-start">
        <div>
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <span class="text-base font-semibold tracking-[0.08em] text-slate-800">
                    Добро стелі. Київ
                </span>

                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                    <a
                        href="{{ $phoneHref }}"
                        @click.prevent="trackPhoneLeadAndNavigate('{{ $phoneHref }}')"
                        class="inline-flex min-h-11 items-center rounded-full border border-teal-200 bg-white/90 px-4 py-2.5 text-xs font-semibold tracking-[0.14em] text-teal-700 transition hover:border-teal-300 hover:bg-white"
                    >
                        {{ $phoneDisplay }}
                    </a>
                    <a
                        href="{{ $messengerHref }}"
                        @click="trackTouch('messenger_click')"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-11 items-center gap-2 rounded-full border border-sky-200 bg-white/90 px-4 py-2.5 text-xs font-semibold tracking-[0.14em] text-sky-700 transition hover:border-sky-300 hover:bg-white"
                    >
                        <img src="{{ $messengerIconSrc }}" alt="" class="h-4 w-4 shrink-0">
                        <span>Telegram</span>
                    </a>
                </div>
            </div>

            <h1 class="mb-5 text-3xl font-semibold leading-tight text-slate-900 md:text-3xl lg:text-4xl">
                Швидкий монтаж натяжних стель за 1–2 дні без зайвого клопоту
            </h1>

            <p class="mb-8 max-w-2xl text-lg leading-relaxed text-slate-600 md:text-xl">
                Виїзд на замір у зручний час, допомога з підбором матеріалів і зрозумілий прорахунок вартості до початку робіт.
            </p>

            <ul class="mb-8 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                <li class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Фіксуємо ціну до монтажу
                </li>
                <li class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Працюємо швидко і якісно
                </li>
                <li class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Гарантія на роботи і матеріали
                </li>
                <li class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Офіційний договір і прозорі умови
                </li>
            </ul>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="#lead-form" @click.prevent="trackTouchAndNavigate('#lead-form', 'lead_form_click')" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-6 py-3.5 text-center text-sm font-semibold text-white transition hover:bg-teal-800">
                    Залишити заявку
                </a>
                <a href="#works" @click.prevent="trackTouchAndNavigate('#works', 'works_click')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-center text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100">
                    Подивитися приклади
                </a>
                <a href="{{ $phoneHref }}" @click.prevent="trackPhoneLeadAndNavigate('{{ $phoneHref }}')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-center text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100">
                    {{ $phoneDisplay }}
                </a>
            </div>
        </div>

        <div class="relative">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_22px_45px_-28px_rgba(15,23,42,0.45)] md:p-8">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-teal-100 blur-2xl"></div>

                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">
                    Швидкий старт
                </p>
                <p class="mt-2 text-2xl font-semibold leading-tight text-slate-900 md:text-3xl">
                    Виїзд на замір у день звернення
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Досвід</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">15+ років</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Проєкти</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">230+ за рік</p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-teal-100 bg-teal-50 p-4">
                    <p class="text-sm font-medium text-teal-800">
                        Безкоштовна консультація та попередній кошторис до початку робіт.
                    </p>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_22px_45px_-28px_rgba(15,23,42,0.45)]">
                <img
                    src="{{ asset('images/hero.jpg') }}"
                    alt="Натяжна стеля з сучасним освітленням"
                    class="h-56 w-full object-cover sm:h-64"
                >
            </div>
        </div>
    </div>
</section>
