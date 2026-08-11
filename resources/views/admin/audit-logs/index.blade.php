@extends('layouts.admin')

@section('title', 'سجل تدقيق الدفع')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6">
        <h1 class="text-2xl font-black text-white">سجل تدقيق الدفع</h1>
        <p class="text-sm text-slate-400 mt-1">كل تغيير في المفاتيح أو الوسائل أو عمليات الرد يسجل هنا بدون حفظ أي قيمة حساسة.</p>
    </div>

    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-950 text-slate-400">
                <tr>
                    <th class="p-4 text-right">الإجراء</th>
                    <th class="p-4 text-right">البوابة</th>
                    <th class="p-4 text-right">النوع</th>
                    <th class="p-4 text-right">التفاصيل</th>
                    <th class="p-4 text-right">بواسطة</th>
                    <th class="p-4 text-right">التوقيت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($logs as $log)
                    <tr>
                        <td class="p-4 text-white font-bold">{{ $log->action }}</td>
                        <td class="p-4 text-slate-300">{{ $log->gateway?->code ?: '-' }}</td>
                        <td class="p-4 text-slate-400 text-xs">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '-' }}</td>
                        <td class="p-4 text-slate-300 text-xs">{{ collect($log->changes)->map(fn ($v, $k) => $k.'='.json_encode($v))->implode(' · ') ?: '-' }}</td>
                        <td class="p-4 text-slate-400">{{ $log->actor ? $log->actor->name : '—' }}</td>
                        <td class="p-4 text-slate-400 text-xs">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">لا توجد سجلات تدقيق بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
@endsection