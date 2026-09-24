<section id="benefits" class="bg-gray-50 px-6 py-16 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 max-w-3xl">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-teal-700">
                {{ $landingCopy['benefits']['eyebrow'] }}
            </p>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900 md:text-3xl">
                {{ $landingCopy['benefits']['title'] }}
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                {{ $landingCopy['benefits']['lead'] }}
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)]">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-sm font-semibold text-teal-700">01</span>
                <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ $landingCopy['benefits']['items'][0]['title'] }}</h3>
                <p class="mt-3 leading-relaxed text-slate-600">
                    {{ $landingCopy['benefits']['items'][0]['text'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)]">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-sm font-semibold text-teal-700">02</span>
                <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ $landingCopy['benefits']['items'][1]['title'] }}</h3>
                <p class="mt-3 leading-relaxed text-slate-600">
                    {{ $landingCopy['benefits']['items'][1]['text'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)]">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-sm font-semibold text-teal-700">03</span>
                <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ $landingCopy['benefits']['items'][2]['title'] }}</h3>
                <p class="mt-3 leading-relaxed text-slate-600">
                    {{ $landingCopy['benefits']['items'][2]['text'] }}
                </p>
            </article>
        </div>
    </div>
</section>
