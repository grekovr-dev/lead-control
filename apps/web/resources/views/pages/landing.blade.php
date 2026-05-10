@extends('layouts.app')

@php
    $phoneDisplay = '+38 (066) 781-07-07';
    $phoneHref = 'tel:+380667810707';
    $messengerLabel = 'Написати у Telegram';
    $messengerHref = 'https://t.me/dobristeli';
    $messengerIconSrc = asset('images/icons/telegram.svg');
    $captureConfig = [
        'clickUrl' => route('capture.click'),
        'touchUrl' => route('capture.touch'),
        'leadFormUrl' => route('capture.leads.form'),
        'leadPhoneClickUrl' => route('capture.leads.phone-click'),
        'leadPhoneCountryCode' => '+380',
        'formSuccessMessage' => 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
        'formValidationMessage' => 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
        'formConflictMessage' => 'Не вдалося зберегти заявку без поточного візиту. Оновіть сторінку та спробуйте ще раз.',
        'formFailureMessage' => 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
        'leadPhoneRequiredMessage' => 'Вкажіть номер телефону.',
        'leadPhoneFormatMessage' => 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
    ];
@endphp

@section('content')
    <script type="application/json" id="landing-capture-config">
        @json($captureConfig)
    </script>

    <main x-data="landingCapture()" x-init="init()" class="relative" :aria-busy="isBootstrapping ? 'true' : 'false'">
        <div
            x-cloak
            x-show.important="isBootstrapping"
            x-transition.opacity
            class="fixed inset-x-0 top-0 z-9999 flex justify-center px-4 pt-4"
        >
        </div>

        <div :inert="isBootstrapping" :class="isBootstrapping ? 'pointer-events-none select-none opacity-60' : ''">
            @include('sections.hero')
            @include('sections.benefits')
            @include('sections.works')
            @include('sections.cta')
            @include('sections.faq')
            @include('sections.lead-form')
            @include('sections.footer')
        </div>
    </main>
@endsection
