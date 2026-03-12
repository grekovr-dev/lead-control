@extends('admin.layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Карточка лида</h2>

                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Имя</dt>
                        <dd class="mt-1 font-medium">{{ $lead->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-500">Телефон</dt>
                        <dd class="mt-1 font-medium">{{ $lead->phone }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-500">Источник</dt>
                        <dd class="mt-1 font-medium">{{ $lead->source ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-500">Создан</dt>
                        <dd class="mt-1 font-medium">{{ $lead->created_at?->format('d.m.Y H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-500">Статус</dt>
                        <dd class="mt-1 font-medium">{{ $lead->status?->label() ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Заметки администратора</h2>

                <form method="POST" action="{{ route('admin.leads.notes.store', $lead) }}" class="mt-4">
                    @csrf

                    <textarea
                        name="note"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2"
                        placeholder="Добавить внутреннюю заметку..."
                    >{{ old('note') }}</textarea>

                    @error('note')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror

                    <button
                        type="submit"
                        class="mt-3 rounded-lg bg-slate-900 px-4 py-2 text-white hover:bg-slate-800"
                    >
                        Добавить заметку
                    </button>
                </form>

                <div class="mt-6 space-y-4">
                    @forelse ($lead->notes as $note)
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="text-sm text-slate-500">
                                {{ $note->created_at?->format('d.m.Y H:i') }}
                            </div>
                            <div class="mt-2 whitespace-pre-line">{{ $note->note }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Заметок пока нет</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Смена статуса</h2>

                <form method="POST" action="{{ route('admin.leads.update-status', $lead) }}" class="mt-4">
                    @csrf
                    @method('PATCH')

                    <select
                        name="status"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected($lead->status?->value === $status->value)
                            >
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>

                    @error('status')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror

                    <button
                        type="submit"
                        class="mt-3 w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    >
                        Сохранить статус
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
