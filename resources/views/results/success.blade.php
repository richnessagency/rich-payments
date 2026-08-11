@php
    $siteName = \Richness\RichPayments\Support\Branding::siteName();
    $logoUrl = \Richness\RichPayments\Support\Branding::logoUrl();
    $showPoweredBy = \Richness\RichPayments\Support\Branding::showPoweredBy();
    $rtl = app()->getLocale() === 'ar';
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $rtl ? 'تم الدفع' : 'Payment successful' }} · {{ $siteName }}</title></head>
<body style="margin:0;font-family:Tahoma,Arial,sans-serif;background:#f0fdf4;color:#111827">
<main style="width:min(640px,calc(100% - 32px));margin:48px auto;background:white;border:1px solid #bbf7d0;border-radius:18px;padding:28px;text-align:center">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height:40px;width:auto;margin-bottom:14px">
    @endif
    <h1 style="margin-top:0">{{ $rtl ? 'تم الدفع بنجاح' : 'Payment successful' }}</h1>
    <p style="color:#166534">{{ $rtl ? 'هذه الصفحة للعرض فقط؛ مصدر الحقيقة هو Webhook مؤكد من بوابة الدفع.' : 'This page is for display only; the source of truth is a verified gateway webhook.' }}</p>
    @if($showPoweredBy)
        <p style="margin-top:28px;color:#9ca3af;font-size:12px">Powered by RichPayments</p>
    @endif
</main>
</body>
</html>