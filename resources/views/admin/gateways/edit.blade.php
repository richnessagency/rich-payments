@extends('layouts.admin')

@section('title', 'إعدادات '.$gateway->name)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6">
        <h1 class="text-2xl font-black text-white">إعدادات {{ $gateway->name }}</h1>
        <p class="text-sm text-slate-400 mt-1">المفاتيح تحفظ مشفرة ولا تظهر مرة أخرى بعد الحفظ.</p>
    </div>

    @if(session('status'))
        <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 p-4 text-sm font-bold">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('rich-payments.admin.gateways.test-connection', $gateway->code) }}" class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6">
        @csrf
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-orange-400 font-extrabold">اختبار الاتصال</h2>
                <p class="text-xs text-slate-400 mt-1">يتحقق من صحة المفاتيح المحفوظة مع Paymob بدون كشف قيمها.</p>
            </div>
            <button class="bg-slate-950 hover:bg-slate-800 border border-slate-700 text-slate-100 rounded-xl px-5 py-2 text-sm font-extrabold">اختبار الاتصال</button>
        </div>
    </form>

    <form method="post" action="{{ route('rich-payments.admin.gateways.update', $gateway->code) }}" class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 space-y-6">
        @csrf
        @method('put')

        <div class="grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="block text-xs font-bold text-slate-300 mb-2">اسم البوابة</span>
                <input name="name" value="{{ old('name', $gateway->name) }}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white" required>
            </label>
            <label class="block">
                <span class="block text-xs font-bold text-slate-300 mb-2">البيئة</span>
                <select name="environment" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white">
                    <option value="test" @selected(old('environment', $gateway->environment) === 'test')>Test</option>
                    <option value="live" @selected(old('environment', $gateway->environment) === 'live')>Live</option>
                </select>
            </label>
            <label class="flex items-center gap-3 mt-6 text-slate-200">
                <input type="checkbox" name="active" value="1" @checked(old('active', $gateway->active))>
                <span>تفعيل البوابة</span>
            </label>
        </div>

        <div class="rounded-2xl border border-slate-800 p-5 space-y-4">
            <h2 class="text-orange-400 font-extrabold">المفاتيح المشفرة</h2>
            @foreach(['secret_key' => 'Secret Key', 'public_key' => 'Public Key', 'hmac_secret' => 'HMAC Secret', 'api_key' => 'API Key'] as $key => $label)
                @php $credential = $gateway->credentials->firstWhere('key_name', $key); @endphp
                <label class="block">
                    <span class="block text-xs font-bold text-slate-300 mb-2">{{ $label }} @if($credential?->masked_preview)<span class="text-slate-500">({{ $credential->masked_preview }})</span>@endif</span>
                    <input name="credentials[{{ $key }}]" type="password" autocomplete="new-password" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white" placeholder="اتركه فارغاً للإبقاء على القيمة الحالية">
                </label>
            @endforeach
        </div>

        <div class="rounded-2xl border border-slate-800 p-5 space-y-4">
            <h2 class="text-orange-400 font-extrabold">وسائل الدفع و Integration IDs</h2>
            @foreach($gateway->methods->sortBy('sort_order') as $method)
                @php
                    $integrationIdentifier = $method->integration_identifier;
                    $integrationPreview = $integrationIdentifier
                        ? str_repeat('•', 12).mb_substr((string) $integrationIdentifier, -4)
                        : null;
                @endphp
                <div class="grid gap-4 md:grid-cols-3 rounded-xl bg-slate-950/50 border border-slate-800 p-4">
                    <label>
                        <span class="block text-xs font-bold text-slate-300 mb-2">العربي</span>
                        <input name="methods[{{ $method->code }}][display_name_ar]" value="{{ old('methods.'.$method->code.'.display_name_ar', $method->display_name_ar) }}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white" required>
                    </label>
                    <label>
                        <span class="block text-xs font-bold text-slate-300 mb-2">English</span>
                        <input name="methods[{{ $method->code }}][display_name_en]" value="{{ old('methods.'.$method->code.'.display_name_en', $method->display_name_en) }}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white">
                    </label>
                    <label>
                        <span class="block text-xs font-bold text-slate-300 mb-2">
                            Integration ID
                            @if($integrationPreview)
                                <span class="text-slate-500">({{ $integrationPreview }})</span>
                            @endif
                        </span>
                        <input name="methods[{{ $method->code }}][integration_identifier]" type="password" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white" placeholder="{{ $integrationPreview ? 'محفوظ - اتركه فارغاً للإبقاء عليه' : 'أدخل Integration ID' }}">
                    </label>
                    <label>
                        <span class="block text-xs font-bold text-slate-300 mb-2">رسوم خدمة %</span>
                        <input name="methods[{{ $method->code }}][fees_percent]" type="number" step="0.01" min="0" value="{{ old('methods.'.$method->code.'.fees_percent', $method->fees_config['percent'] ?? null) }}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white" placeholder="مثال 1.5">
                    </label>
                    <label>
                        <span class="block text-xs font-bold text-slate-300 mb-2">رسوم ثابتة (minor)</span>
                        <input name="methods[{{ $method->code }}][fees_fixed_minor]" type="number" min="0" value="{{ old('methods.'.$method->code.'.fees_fixed_minor', $method->fees_config['fixed_minor'] ?? null) }}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white" placeholder="مثال 250">
                    </label>
                    <label class="flex items-center gap-3 mt-6 text-slate-200">
                        <input type="checkbox" name="methods[{{ $method->code }}][active]" value="1" @checked(old('methods.'.$method->code.'.active', $method->active))>
                        <span>مفعلة</span>
                    </label>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button class="bg-orange-500 hover:bg-orange-600 text-white rounded-xl px-6 py-3 font-extrabold">حفظ إعدادات الدفع</button>
        </div>
    </form>
</div>
@endsection
