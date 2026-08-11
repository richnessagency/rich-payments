@php
    $siteName = \Richness\RichPayments\Support\Branding::siteName();
    $logoUrl = \Richness\RichPayments\Support\Branding::logoUrl();
    $showPoweredBy = \Richness\RichPayments\Support\Branding::showPoweredBy();
    $rtl = app()->getLocale() === 'ar';
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $rtl ? 'فشل الدفع' : 'Payment failed' }} · {{ $siteName }}</title></head>
<body style="margin:0;font-family:Tahoma,Arial,sans-serif;background:#fef2f2;color:#111827">
<main style="width:min(640px,calc(100% - 32px));margin:48px auto;background:white;border:1px solid #fecaca;border-radius:18px;padding:28px;text-align:center">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height:40px;width:auto;margin-bottom:14px">
    @endif
    <h1 style="margin-top:0">{{ $rtl ? 'لم تكتمل عملية الدفع' : 'Payment did not complete' }}</h1>
    <p style="color:#991b1b">{{ session('rich_payments_error', $rtl ? 'يمكنك المحاولة مرة أخرى أو اختيار وسيلة دفع مختلفة.' : 'You can try again or choose a different payment method.') }}</p>
    @if($showPoweredBy)
        <p style="margin-top:28px;color:#9ca3af;font-size:12px">Powered by RichPayments</p>
    @endif
</main>
</body>
</html>