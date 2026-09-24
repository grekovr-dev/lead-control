<section id="works" class="bg-gray-50 px-6 py-16 md:py-20">
    <div class="mx-auto max-w-6xl">
        <div class="mb-10 max-w-3xl">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-teal-700">
                {{ $landingCopy['works']['eyebrow'] }}
            </p>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900 md:text-3xl">
                {{ $landingCopy['works']['title'] }}
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                {{ $landingCopy['works']['lead'] }}
            </p>
        </div>

        @php
            $workImages = [
                ['src' => 'images/bathroom-ceiling.jpg', 'position' => 'object-top'],
                ['src' => 'images/kitchen-ceiling.jpg', 'position' => 'object-center'],
                ['src' => 'images/bedroom-ceiling.jpg', 'position' => 'object-top'],
            ];
        @endphp

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($landingCopy['works']['items'] as $index => $work)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)]">
                    <div class="relative h-52 overflow-hidden bg-slate-100">
                        <img
                            src="{{ asset($workImages[$index]['src']) }}"
                            alt="{{ $work['image_alt'] }}"
                            class="h-full w-full object-cover {{ $workImages[$index]['position'] }}"
                        >
                        <span class="absolute bottom-4 right-4 inline-flex rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                            {{ $work['tag'] }}
                        </span>
                    </div>
                    <div class="space-y-3 p-5">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $work['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-slate-600">{{ $work['text'] }}</p>
                        <div class="flex flex-wrap gap-2 pt-1 text-xs">
                            <span class="rounded-full bg-teal-100 px-2.5 py-1 font-medium text-teal-800">{{ $work['area'] }}</span>
                            <span class="rounded-full bg-slate-200 px-2.5 py-1 font-medium text-slate-700">{{ $work['duration'] }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
