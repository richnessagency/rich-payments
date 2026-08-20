@php
    $siteName = \Richness\RichPayments\Support\Branding::siteName();
    $logoUrl = \Richness\RichPayments\Support\Branding::logoUrl();
    $showPoweredBy = \Richness\RichPayments\Support\Branding::showPoweredBy();
    $rtl = app()->getLocale() === 'ar';
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $rtl ? 'جاري تأكيد الدفع' : 'Confirming Payment' }} · {{ $siteName }}</title>
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #fed7aa;
            border-top: 5px solid #f97316;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        .btn-primary {
            display: inline-block;
            background-color: #f97316;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            margin: 8px;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #ea580c;
        }
        .btn-secondary {
            display: inline-block;
            background-color: #f3f4f6;
            color: #4b5563;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            margin: 8px;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
    </style>
</head>
<body style="margin:0;font-family:Segoe UI,Tahoma,Arial,sans-serif;background:#fff7ed;color:#111827;display:flex;align-items:center;justify-content:center;min-height:100vh">
<main style="width:min(540px,calc(100% - 32px));background:white;border:1px solid #fed7aa;border-radius:18px;padding:40px 28px;text-align:center;box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05)">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height:50px;width:auto;margin-bottom:24px">
    @endif
    
    <div class="spinner"></div>
    
    <h1 style="margin-top:0;font-size:24px;margin-bottom:12px">{{ $rtl ? 'جاري تأكيد عملية الدفع' : 'Confirming your payment' }}</h1>
    
    <p style="color:#4b5563;font-size:16px;line-height:1.6;margin-bottom:28px">
        {{ $rtl ? 'نحن بانتظار تأكيد بوابة الدفع الإلكتروني حالياً. سيتم تحديث حالة طلبك وتأكيده تلقائياً خلال لحظات بمجرد إتمام الدفع. يرجى عدم إغلاق هذه الصفحة.' : 'We are currently waiting for confirmation from the payment gateway. Your order will be confirmed automatically in a few moments once the payment is completed. Please do not close this page.' }}
    </p>

    <div style="margin-top:20px">
        <a href="{{ route('orders.lookup') }}" class="btn-primary">
            {{ $rtl ? 'الذهاب لمتابعة الطلب' : 'Track Order Status' }}
        </a>
        <a href="/" class="btn-secondary">
            {{ $rtl ? 'العودة للرئيسية' : 'Return to Homepage' }}
        </a>
    </div>

    @if($showPoweredBy)
        <p style="margin-top:36px;color:#9ca3af;font-size:12px;margin-bottom:0">Powered by RichPayments</p>
    @endif
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const reference = "{{ $reference ?? '' }}";
        if (!reference) return;

        const checkStatus = () => {
            fetch("/{{ config('rich-payments.route_prefix', 'payments') }}/status/" + encodeURIComponent(reference))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                })
                .catch(error => console.error('Error checking payment status:', error));
        };

        // Poll every 3 seconds
        const intervalId = setInterval(checkStatus, 3000);
        
        // Initial check after 3 seconds
        setTimeout(checkStatus, 3000);
    });
</script>
</body>
</html>