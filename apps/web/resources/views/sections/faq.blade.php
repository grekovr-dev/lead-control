<section id="faq" class="bg-gray-50 px-6 py-16 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 max-w-3xl">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-teal-700">
                {{ $landingCopy['faq']['eyebrow'] }}
            </p>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900 md:text-3xl">
                {{ $landingCopy['faq']['title'] }}
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                {{ $landingCopy['faq']['lead'] }}
            </p>
        </div>

        <div class="grid gap-4">
            @foreach ($landingCopy['faq']['items'] as $item)
            <details class="group rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)]">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-lg font-semibold text-slate-900">
                    <span>{{ $item['question'] }}</span>
                    <span class="text-teal-700 transition group-open:rotate-45">+</span>
                </summary>
                <p class="mt-4 leading-relaxed text-slate-600">
                    {{ $item['answer'] }}
                </p>
            </details>
            @endforeach
        </div>
    </div>
</section>
