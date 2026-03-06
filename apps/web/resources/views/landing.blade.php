@extends('layouts.app')

@section('title', 'Лендинг')

@section('content')
    <section class="rounded-2xl bg-white p-8 shadow-sm border">
        <h1 class="text-3xl font-bold tracking-tight">
            TAILWIND OK (v4)
        </h1>

        <p class="mt-4 text-slate-600 max-w-2xl">
            Если ты видишь этот блок белым, с отступами, тенью и большим заголовком —
            Tailwind точно работает.
        </p>

        <div class="mt-6 flex gap-3">
            <a href="#contact" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-white hover:bg-slate-800">
                CTA кнопка
            </a>
            <a href="#features" class="inline-flex items-center rounded-xl border px-4 py-2 hover:bg-slate-50">
                Вторая кнопка
            </a>
        </div>
    </section>

    <section id="features" class="mt-10 grid md:grid-cols-3 gap-4">
        <div class="rounded-2xl bg-white p-6 border shadow-sm">
            <div class="font-semibold">Блок 1</div>
            <div class="mt-2 text-sm text-slate-600">Проверка сетки/карточек</div>
        </div>
        <div class="rounded-2xl bg-white p-6 border shadow-sm">
            <div class="font-semibold">Блок 2</div>
            <div class="mt-2 text-sm text-slate-600">Проверка отступов</div>
        </div>
        <div class="rounded-2xl bg-white p-6 border shadow-sm">
            <div class="font-semibold">Блок 3</div>
            <div class="mt-2 text-sm text-slate-600">Проверка текста</div>
        </div>
    </section>

    <section id="contact" class="mt-10 rounded-2xl bg-white p-6 border shadow-sm">
        <div class="font-semibold">Контакт</div>
        <p class="mt-2 text-sm text-slate-600">Позже тут будет форма заявки.</p>
    </section>
@endsection
