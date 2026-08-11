@extends('layouts.admin')

@section('title', 'محاولات الدفع')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6">
        <h1 class="text-2xl font-black text-white">محاولات الدفع</h1>
        <p class="text-sm text-slate-400 mt-1">مراجعة عمليات RichPayments وتشغيل Transaction Inquiry عند الحاجة.</p>
    </div>

    @if(session('status'))
        <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 p-4 text-sm font-bold">{{ session('status') }}</div>
    @endif

    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-950 text-slate-400">
                <tr>
                    <th class="p-4 text-right">المرجع</th>
                    <th class="p-4 text-right">البوابة</th>
                    <th class="p-4 text-right">الحالة</th>
                    <th class="p-4 text-right">القيمة</th>
                    <th class="p-4 text-right">معاملة خارجية</th>
                    <th class="p-4 text-right">إجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($attempts as $attempt)
                    <tr>
                        <td class="p-4 text-white font-bold">{{ $attempt->merchant_reference ?: $attempt->public_id }}</td>
                        <td class="p-4 text-slate-300">{{ $attempt->gateway_code }} / {{ $attempt->method_code ?: '-' }}</td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-xs font-black bg-slate-800 text-slate-200">{{ $attempt->status->value }}</span>
                        </td>
                        <td class="p-4 text-slate-300">{{ $attempt->currency }} {{ number_format($attempt->amount_minor / 100, 2) }}</td>
                        <td class="p-4 text-slate-400 font-mono">{{ $attempt->transactions->last()?->external_transaction_id ?: $attempt->external_reference ?: '-' }}</td>
                        <td class="p-4">
                            <form method="post" action="{{ route('rich-payments.admin.attempts.inquire', $attempt) }}" class="flex items-center gap-2 mb-2">
                                @csrf
                                <input name="external_transaction_id" value="{{ $attempt->transactions->last()?->external_transaction_id ?: $attempt->external_reference }}" class="w-36 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs" placeholder="Transaction ID">
                                <button class="bg-orange-500 hover:bg-orange-600 text-white rounded-lg px-3 py-2 text-xs font-extrabold">استعلام</button>
                            </form>
                            <div class="flex items-center gap-2">
                                <form method="post" action="{{ route('rich-payments.admin.attempts.refund', $attempt) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="external_transaction_id" value="{{ $attempt->transactions->last()?->external_transaction_id ?: $attempt->external_reference }}">
                                    <input name="amount_minor" type="number" min="1" value="{{ $attempt->amount_minor }}" class="w-24 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs" placeholder="minor">
                                    <button class="bg-red-500/90 hover:bg-red-600 text-white rounded-lg px-3 py-2 text-xs font-extrabold">رد</button>
                                </form>
                                <form method="post" action="{{ route('rich-payments.admin.attempts.void', $attempt) }}" class="flex gap-2">
                                    @csrf
                                    <input type="hidden" name="external_transaction_id" value="{{ $attempt->transactions->last()?->external_transaction_id ?: $attempt->external_reference }}">
                                    <button class="bg-slate-700 hover:bg-slate-600 text-slate-100 rounded-lg px-3 py-2 text-xs font-extrabold">إلغاء</button>
                                </form>
                                <form method="post" action="{{ route('rich-payments.admin.attempts.capture', $attempt) }}" class="flex gap-2">
                                    @csrf
                                    <input type="hidden" name="external_transaction_id" value="{{ $attempt->transactions->last()?->external_transaction_id ?: $attempt->external_reference }}">
                                    <button class="bg-emerald-600/90 hover:bg-emerald-600 text-white rounded-lg px-3 py-2 text-xs font-extrabold">تحصيل</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">لا توجد محاولات دفع بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $attempts->links() }}
</div>
@endsection
