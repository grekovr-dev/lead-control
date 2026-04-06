@php
    $leadPhoneCountryCode = $captureConfig['leadPhoneCountryCode'] ?? '+380';
@endphp

<section id="lead-form" class="px-6 pb-20 pt-12 md:pb-24">
    <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="min-w-0 rounded-3xl border border-slate-200 bg-white p-7 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45)] md:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-teal-700">Заявка</p>
            <h2 class="mt-3 text-3xl font-semibold leading-tight text-slate-900">
                Розрахуємо вартість під ваш запит
            </h2>
            <p class="mt-4 leading-relaxed text-slate-600">
                Заповніть коротку форму. На цьому етапі ми лише збираємо контакт для консультації без складних кроків.
            </p>

            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Швидко уточнимо деталі вашого запиту
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Підбір матеріалу під бюджет
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Попередній кошторис до виїзду
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Підкажемо варіант без зайвих витрат
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">✓</span>
                    Пояснимо, що впливає на кінцеву вартість
                </li>
            </ul>
        </div>

        <div class="min-w-0 rounded-3xl border border-slate-200 bg-white p-7 shadow-[0_20px_40px_-28px_rgba(15,23,42,0.45)] md:p-8">
            <div
                x-cloak
                x-show="leadFormState === 'success'"
                class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                <span x-text="leadFormMessage"></span>
            </div>

            <div
                x-cloak
                x-show="leadFormState === 'validation-error'"
                class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
            >
                <p x-text="leadFormMessage"></p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <template x-for="messages in Object.values(leadFormFieldErrors)" :key="messages[0]">
                        <li x-text="messages[0]"></li>
                    </template>
                </ul>
            </div>

            <div
                x-cloak
                x-show="leadFormState === 'server-error'"
                class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
            >
                <span x-text="leadFormMessage"></span>
            </div>

            <form method="POST" class="space-y-5" :aria-busy="isBootstrapping || isSubmittingLeadForm ? 'true' : 'false'" @submit.prevent="submitLeadForm($event)">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Ім'я</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        placeholder="Ваше ім'я"
                        :disabled="isBootstrapping || isSubmittingLeadForm"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-100"
                    >
                    <p x-cloak x-show="leadFormFieldErrors.name" class="mt-2 text-xs text-rose-600" x-text="leadFormFieldErrors.name ? leadFormFieldErrors.name[0] : ''"></p>
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Телефон</label>
                    <div class="flex overflow-hidden rounded-xl border border-slate-300 bg-slate-50 transition focus-within:border-teal-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-teal-100">
                        <span class="inline-flex items-center border-r border-slate-300 bg-slate-100 px-4 text-sm font-semibold text-slate-700">
                            {{ $leadPhoneCountryCode }}
                        </span>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone') }}"
                            inputmode="numeric"
                            autocomplete="tel-national"
                            spellcheck="false"
                            maxlength="20"
                            title="Введіть 9 цифр після +380, наприклад 50 111 22 33"
                            placeholder="50 111 22 33"
                            @blur="normalizeLeadPhoneField($event)"
                            :disabled="isBootstrapping || isSubmittingLeadForm"
                            class="min-w-0 flex-1 border-0 bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                        >
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">
                        Введіть 9 цифр після <span class="font-medium text-slate-700">{{ $leadPhoneCountryCode }}</span>, наприклад <span class="whitespace-nowrap font-medium text-slate-700">50 111 22 33</span>.
                    </p>
                    <p x-cloak x-show="leadFormFieldErrors.phone" class="mt-2 text-xs text-rose-600" x-text="leadFormFieldErrors.phone ? leadFormFieldErrors.phone[0] : ''"></p>
                </div>

                <button
                    type="submit"
                    :disabled="isBootstrapping || isSubmittingLeadForm"
                    class="w-full rounded-xl bg-teal-700 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                >
                    <span x-cloak x-show="!isSubmittingLeadForm">Надіслати заявку</span>
                    <span x-cloak x-show="isSubmittingLeadForm">Надсилаємо заявку...</span>
                </button>

                <p class="text-xs leading-relaxed text-slate-500">
                    Натискаючи кнопку, ви погоджуєтесь на обробку контактних даних для зворотного зв'язку.
                </p>

                <a
                    href="{{ $messengerHref }}"
                    @click="trackTouch('messenger_click')"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-3 self-start text-sm font-semibold text-sky-700 transition hover:text-sky-800"
                >
                    <img src="{{ $messengerIconSrc }}" alt="" class="h-5 w-5 shrink-0">
                    <span>Або написати у Telegram</span>
                </a>
            </form>
        </div>
    </div>
</section>
