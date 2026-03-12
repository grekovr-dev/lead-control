@extends('admin.layouts.app')

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left">Имя</th>
                    <th class="px-4 py-3 text-left">Телефон</th>
                    <th class="px-4 py-3 text-left">Источник</th>
                    <th class="px-4 py-3 text-left">Статус</th>
                    <th class="px-4 py-3 text-left">Дата создания</th>
                    <th class="px-4 py-3 text-left"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3">{{ $lead->name }}</td>
                        <td class="px-4 py-3">{{ $lead->phone }}</td>
                        <td class="px-4 py-3">{{ $lead->source ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $lead->status?->label() ?? $lead->status?->value ?? '—' }}
                        </td>
                        <td class="px-4 py-3">{{ $lead->created_at?->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="text-blue-600 hover:underline">
                                Открыть
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                            Лидов пока нет
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $leads->links() }}
    </div>
@endsection
