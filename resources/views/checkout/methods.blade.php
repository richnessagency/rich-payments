@php
    $siteName = \Richness\RichPayments\Support\Branding::siteName();
    $logoUrl = \Richness\RichPayments\Support\Branding::logoUrl();
    $primaryColor = \Richness\RichPayments\Support\Branding::primaryColor();
    $accentColor = \Richness\RichPayments\Support\Branding::accentColor();
    $showPoweredBy = \Richness\RichPayments\Support\Branding::showPoweredBy();
    $rtl = app()->getLocale() === 'ar';
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $rtl ? 'اختيار وسيلة الدفع' : 'Choose Payment Method' }} · {{ $siteName }}</title>
    <style>
        body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#fff7ed;color:#15120f}
        main{width:min(920px,calc(100% - 32px));margin:36px auto}
        .panel{background:#fff;border:1px solid {{ $accentColor }};border-radius:18px;padding:24px;box-shadow:0 18px 45px rgba(124,45,18,.08)}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:16px}
        .brand img{height:40px;width:auto}
        h1{margin:0 0 8px;font-size:clamp(26px,4vw,44px)}
        p{margin:0 0 22px;color:#7c2d12}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
        .method{display:block;border:1px solid #e5ded5;border-radius:14px;padding:16px;background:#fffaf3}
        .method strong{display:block;margin-bottom:4px}
        .btn{border:0;border-radius:999px;background:{{ $primaryColor }};color:#fff;padding:12px 18px;font-weight:800;cursor:pointer;margin-top:12px}
        input{width:100%;border:1px solid #e5ded5;border-radius:12px;padding:10px;margin-top:8px}
        .powered{margin-top:26px;text-align:center;color:#9ca3af;font-size:12px}
    </style>
</head>
<body>
<main>
    <section class="panel">
        <div class="brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}">
            @endif
            <h1 style="font-size:24px;margin:0">{{ $siteName }}</h1>
        </div>
        <h1>{{ $rtl ? 'اختار طريقة الدفع' : 'Choose payment method' }}</h1>
        <p>{{ $rtl ? 'هذه شاشة جاهزة من RichPayments ويمكن نشرها وتعديلها داخل مشروعك.' : 'This ready view ships with RichPayments and can be published for customization.' }}</p>

        @if($gateways->isEmpty())
            <p>{{ $rtl ? 'لا توجد بوابات دفع مفعلة حالياً.' : 'No active payment gateways are available.' }}</p>
        @endif

        <div class="grid">
            @foreach($gateways as $gateway)
                @foreach($gateway->methods as $method)
                    <form class="method" method="post" action="{{ route('rich-payments.start') }}">
                        @csrf
                        <strong>{{ $rtl ? $method->display_name_ar : ($method->display_name_en ?: $method->display_name_ar) }}</strong>
                        <span>{{ $gateway->name }}</span>
                        <input type="hidden" name="gateway" value="{{ $gateway->code }}">
                        <input type="hidden" name="method" value="{{ $method->code }}">
                        <input type="number" name="amount_minor" placeholder="Amount minor units" required>
                        <input type="text" name="merchant_reference" placeholder="Order reference" required>
                        <button class="btn" type="submit">{{ $rtl ? 'ادفع الآن' : 'Pay now' }}</button>
                    </form>
                @endforeach
            @endforeach
        </div>

        @if($showPoweredBy)
            <p class="powered">Powered by RichPayments</p>
        @endif
    </section>
</main>
</body>
</html>