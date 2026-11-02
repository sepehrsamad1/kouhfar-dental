@extends('layouts.staff')

@section('title', 'مدیریت دستیار هوشمند')

@section('content')
<main class="staff-config">
    <h1>مدیریت دستیار هوشمند</h1>
    <p>هزینه تخمینی بر اساس تعرفه‌های پیکربندی‌شده و توکن‌های ثبت‌شده محاسبه می‌شود. این عدد صورتحساب قطعی ارائه‌دهنده نیست.</p>
    <section>
        <h2>مصرف ماه جاری</h2>
        <p>مصرف تخمینی: {{ number_format($summary['monthly_spend_irr']) }} ریال</p>
        <p>سقف ماهانه: {{ number_format($summary['monthly_budget_irr']) }} ریال</p>
        <p>درصد مصرف: {{ $summary['percentage'] }}٪</p>
        <p>وضعیت درخواست‌های جدید: {{ $summary['blocked'] ? 'مسدود' : 'مجاز طبق کنترل بودجه' }}</p>
        <p>ارائه‌دهنده و مدل پیکربندی‌شده: {{ $summary['provider'] }} / {{ $summary['model'] }}</p>
        <p>وضعیت اتصال: {{ $summary['provider_connection_status'] === 'fake' ? 'ارائه‌دهنده آزمایشی پیکربندی شده است؛ سرویس خارجی متصل نیست.' : 'وضعیت اتصال ارائه‌دهنده هنوز تأیید نشده است.' }}</p>
        <p>وضعیت سهمیه واقعی ارائه‌دهنده: نامشخص؛ ارائه‌دهنده آزمایشی اطلاعات اعتبار یا سهمیه واقعی ارائه نمی‌کند.</p>
        @if ($summary['alerts'] !== [])
            <ul role="status">
                @foreach ($summary['alerts'] as $level)
                    <li>هشدار مصرف بودجه: {{ $level }}٪ سقف ماهانه مصرف شده است.</li>
                @endforeach
            </ul>
        @endif
    </section>

    <section>
        <h2>تنظیمات کنترل (فقط مدیر)</h2>
        <form method="post" action="{{ route('staff.ai.update') }}">
            @csrf
            @method('PATCH')
            <label>سقف بودجه ماهانه (ریال)
                <input type="number" name="monthly_budget_irr" min="1000" max="1000000000000" value="{{ old('monthly_budget_irr', $settings->monthly_budget_irr) }}" required>
            </label>
            @foreach ($summary['model_limits'] as $model => $limits)
                <article>
                    <h3>محدودیت مستقل مدل: {{ $model }}</h3>
                    <label>سقف داخلی توکن ماهانه
                        <input type="number" name="provider_token_limits[{{ $model }}]" min="1" max="1000000000000" value="{{ old('provider_token_limits.'.$model, data_get($settings->provider_token_limits, $model, '')) }}" placeholder="بدون سقف مستقل">
                    </label>
                    <label>سقف مالی ماهانه مدل (ریال)
                        <input type="number" name="model_budget_limits_irr[{{ $model }}]" min="1000" max="1000000000000" value="{{ old('model_budget_limits_irr.'.$model, data_get($settings->model_budget_limits_irr, $model, '')) }}" placeholder="بدون سقف مستقل">
                    </label>
                    <p>مصرف توکن: {{ number_format($limits['tokens_used']) }} / {{ $limits['token_limit'] === null ? 'سقف مستقل تنظیم نشده' : number_format($limits['token_limit']) }} | مصرف مالی: {{ number_format($limits['spend_irr']) }} / {{ $limits['budget_limit_irr'] === null ? 'سقف مستقل تنظیم نشده' : number_format($limits['budget_limit_irr']) }} ریال</p>
                    @foreach ($limits['token_alerts'] as $level)
                        <p role="alert">هشدار سقف داخلی توکن مدل {{ $model }}: {{ $level }}٪ مصرف شده است.</p>
                    @endforeach
                    @foreach ($limits['budget_alerts'] as $level)
                        <p role="alert">هشدار سقف مالی مدل {{ $model }}: {{ $level }}٪ مصرف شده است.</p>
                    @endforeach
                </article>
            @endforeach
            <label><input type="checkbox" name="emergency_disabled" value="1" @checked(old('emergency_disabled', $settings->emergency_disabled))> غیرفعال‌سازی اضطراری دستیار هوشمند</label>
            <button type="submit">ذخیره کنترل‌ها</button>
        </form>
        <p>آستانه‌های مالی کلی و هر سقف مالی مدل: ۵۰٪، ۸۰٪ و ۱۰۰٪. آستانه سقف داخلی توکن هر مدل: ۸۰٪ و ۹۵٪. رسیدن هر مدل به سقف مستقل خودش، فقط همان مدل را متوقف می‌کند؛ سقف کلی ماهانه همه مدل‌ها را متوقف می‌کند.</p>
        <p>آستانه سهمیه مدل: ۸۰٪ و ۹۵٪؛ در پیکربندی فعلی قابل دریافت از ارائه‌دهنده نیست و به‌صورت «نامشخص» باقی می‌ماند.</p>
    </section>

    <section>
        <h2>مصرف به تفکیک ارائه‌دهنده و مدل</h2>
        @forelse ($usage as $row)
            <article>
                <h3>{{ $row->provider }} / {{ $row->model }} — {{ $row->capability }}</h3>
                <p>توکن ورودی: {{ number_format((int) $row->input_tokens) }}</p>
                <p>توکن خروجی: {{ number_format((int) $row->output_tokens) }}</p>
                <p>مجموع توکن: {{ number_format((int) $row->input_tokens + (int) $row->output_tokens) }}</p>
                <p>درخواست‌های موفق ثبت‌شده: {{ number_format((int) $row->request_count) }}</p>
                <p>هزینه تخمینی: {{ number_format((float) $row->estimated_cost_irr, 2) }} ریال</p>
                <p>هزینه واقعی ارائه‌دهنده: {{ $row->actual_cost_irr === null ? 'در دسترس نیست' : number_format((float) $row->actual_cost_irr, 2).' ریال' }}</p>
            </article>
        @empty
            <p>در چرخه جاری هنوز مصرفی ثبت نشده است.</p>
        @endforelse
    </section>

    <section>
        <h2>مصرف روزانه به تفکیک قابلیت</h2>
        @forelse ($dailyUsage as $row)
            <article>
                <h3>{{ $row->day }} — {{ $row->feature }}</h3>
                <p>{{ $row->provider }} / {{ $row->model }} / {{ $row->capability }}</p>
                <p>توکن ورودی: {{ number_format((int) $row->input_tokens) }} | خروجی: {{ number_format((int) $row->output_tokens) }} | مجموع: {{ number_format((int) $row->input_tokens + (int) $row->output_tokens) }}</p>
                <p>هزینه تخمینی: {{ number_format((float) $row->estimated_cost_irr, 2) }} ریال | هزینه واقعی: {{ $row->actual_cost_irr === null ? 'در دسترس نیست' : number_format((float) $row->actual_cost_irr, 2).' ریال' }}</p>
                <p>تعداد درخواست‌ها: {{ number_format((int) $row->request_count) }}</p>
            </article>
        @empty
            <p>مصرف روزانه‌ای ثبت نشده است.</p>
        @endforelse
    </section>

    <section>
        <h2>آخرین درخواست‌ها با زمان ثبت</h2>
        @forelse ($recentRequests as $request)
            <article>
                <p>{{ $request->created_at->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s') }} — {{ $request->provider }} / {{ $request->model }} / {{ $request->capability }}</p>
                <p>وضعیت: {{ $request->status }} | ورودی: {{ $request->input_tokens }} | خروجی: {{ $request->output_tokens }} | هزینه تخمینی: {{ number_format((float) $request->estimated_cost_irr, 2) }} ریال</p>
            </article>
        @empty
            <p>درخواستی ثبت نشده است.</p>
        @endforelse
    </section>

    <section>
        <h2>درخواست‌های موفق و ناموفق</h2>
        @forelse ($requests as $row)
            <p>{{ $row->provider }} / {{ $row->model }} / {{ $row->capability }} — {{ $row->status }}: {{ $row->request_count }}</p>
        @empty
            <p>در چرخه جاری درخواستی ثبت نشده است.</p>
        @endforelse
    </section>
</main>
@endsection
