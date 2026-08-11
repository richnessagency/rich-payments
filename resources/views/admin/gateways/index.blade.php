@extends('layouts.admin')

@section('title', 'RichPayments')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white">RichPayments</h1>
                <p class="text-sm text-slate-400 mt-1">إدارة بوابات الدفع ووسائل الدفع والمفاتيح المشفرة.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('rich-payments.admin.attempts.index') }}" class="bg-slate-950 hover:bg-slate-800 border border-slate-700 text-slate-100 rounded-xl px-4 py-2 text-xs font-extrabold">محاولات الدفع</a>
                <a href="{{ route('rich-payments.admin.audit-logs.index') }}" class="bg-slate-950 hover:bg-slate-800 border border-slate-700 text-slate-100 rounded-xl px-4 py-2 text-xs font-extrabold">سجل التدقيق</a>
            </div>
        </div>
    </div>

    <div class="grid gap-4">
        @forelse($gateways as $gateway)
            <a href="{{ route('rich-payments.admin.gateways.edit', $gateway->code) }}" class="block bg-slate-900/90 border border-slate-800 rounded-2xl p-5 hover:border-orange-500 transition">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-white font-extrabold">{{ $gateway->name }}</h2>
                        <p class="text-xs text-slate-400 mt-1">{{ $gateway->code }} · {{ $gateway->environment }}</p>
                    </div>
                    <span class="text-xs font-black px-3 py-1 rounded-full {{ $gateway->active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-700 text-slate-300' }}">
                        {{ $gateway->active ? 'مفعلة' : 'متوقفة' }}
                    </span>
                </div>
            </a>
        @empty
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 text-slate-300">
                لا توجد بوابات بعد. شغل seeder أو أنشئ بوابة Paymob من migration/installer الحزمة.
            </div>
        @endforelse
    </div>
</div>
@endsection
