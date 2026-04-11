<footer class="border-t border-slate-200 bg-white/90 px-6 py-8">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
        <p>© 2026 Добрі стелі. Київ. Всі права захищено.</p>
        <div class="flex flex-wrap items-center gap-4">
            <a href="#benefits" @click.prevent="navigateAfterReady('#benefits')" class="transition hover:text-slate-700">Переваги</a>
            <a href="#works" @click.prevent="trackTouchAndNavigate('#works', 'works_click')" class="transition hover:text-slate-700">Роботи</a>
            <a href="#lead-form" @click.prevent="trackTouchAndNavigate('#lead-form', 'lead_form_click')" class="transition hover:text-slate-700">Залишити заявку</a>
            <a href="{{ $messengerHref }}" @click="trackTouch('messenger_click')" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 transition hover:text-slate-700">
                <img src="{{ $messengerIconSrc }}" alt="Telegram" class="h-4 w-4 shrink-0">
                <span>Telegram</span>
            </a>
            <a href="{{ $phoneHref }}" @click.prevent="trackPhoneLeadAndNavigate('{{ $phoneHref }}')" class="transition hover:text-slate-700">
                {{ $phoneDisplay }}
            </a>
        </div>
    </div>
</footer>
