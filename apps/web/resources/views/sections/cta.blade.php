<section class="px-6 py-16 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="relative overflow-hidden rounded-3xl border border-teal-200 bg-linear-to-r from-teal-700 via-teal-600 to-cyan-600 px-8 py-12 text-white md:px-12">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 left-10 h-52 w-52 rounded-full bg-cyan-300/25 blur-3xl"></div>

            <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <h2 class="text-3xl font-semibold leading-tight md:text-4xl">
                        Потрібен швидкий прорахунок вартості?
                    </h2>
                    <p class="mt-4 text-lg leading-relaxed text-teal-50/95">
                        Залиште заявку, і ми зв'яжемося з вами для уточнення деталей та підготуємо попередню оцінку.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                    <a href="#lead-form" @click.prevent="trackTouchAndNavigate('#lead-form', 'lead_form_click')" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50">
                        Отримати консультацію
                    </a>
                    <a href="#works" @click.prevent="trackTouchAndNavigate('#works', 'works_click')" class="inline-flex items-center justify-center rounded-xl border border-white/45 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/20">
                        Переглянути роботи
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
