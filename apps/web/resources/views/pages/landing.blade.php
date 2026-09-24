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
        'landingLocale' => $landingGeo->locale,
        'leadPhoneCountryCode' => '+380',
        'formSuccessMessage' => $landingCopy['capture']['success'],
        'formValidationMessage' => $landingCopy['capture']['validation'],
        'formConflictMessage' => $landingCopy['capture']['conflict'],
        'formFailureMessage' => $landingCopy['capture']['failure'],
        'leadPhoneRequiredMessage' => $landingCopy['capture']['phone_required'],
        'leadPhoneFormatMessage' => $landingCopy['capture']['phone_format'],
        'validationNameMustBeStringMessage' => $landingCopy['capture']['name_string'],
        'validationNameTooLongMessage' => $landingCopy['capture']['name_long'],
        'validationGenericMessage' => $landingCopy['capture']['generic_validation'],
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
