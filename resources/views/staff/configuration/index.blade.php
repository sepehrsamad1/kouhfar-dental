<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تنظیمات کلینیک | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main>
    <h1>تنظیمات کلینیک</h1>
    <p>منطقه زمانی برنامه کلینیک: Asia/Tehran. این صفحه موتور تعیین زمان‌های آزاد نیست.</p>
    <p>سیاست تخصیص یونیت‌ها و فضای مشاوره: placeholder — owner to confirm.</p>
    @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
    @if ($errors->any())
        <ul role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif

    <section>
        <h2>خدمات</h2>
        @foreach ($services as $service)
            <form method="post" action="{{ route('staff.configuration.services.update', $service) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="version" value="{{ $service->version }}">
                <label>نام خدمت <input name="name_fa" value="{{ $service->name_fa }}" maxlength="200" required @disabled(! $canManage)></label>
                <label>مدت (دقیقه) <input type="number" name="duration_minutes" min="1" max="1440" value="{{ $service->duration_minutes }}" required @disabled(! $canManage)></label>
                <label>وقفه قبل <input type="number" name="buffer_before_minutes" min="0" max="1440" value="{{ $service->buffer_before_minutes }}" required @disabled(! $canManage)></label>
                <label>وقفه بعد <input type="number" name="buffer_after_minutes" min="0" max="1440" value="{{ $service->buffer_after_minutes }}" required @disabled(! $canManage)></label>
                <input type="hidden" name="is_active" value="0" @disabled(! $canManage)><label>فعال <input type="checkbox" name="is_active" value="1" @checked($service->is_active) @disabled(! $canManage)></label>
                <button type="submit" @disabled(! $canManage)>ذخیره</button>
            </form>
        @endforeach
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.services.store') }}">@csrf
                <h3>افزودن خدمت</h3>
                <label>نام <input name="name_fa" maxlength="200" required></label>
                <label>مدت (دقیقه) <input type="number" name="duration_minutes" min="1" max="1440" required></label>
                <label>وقفه قبل (دقیقه) <input type="number" name="buffer_before_minutes" min="0" max="1440" value="0" required></label>
                <label>وقفه بعد (دقیقه) <input type="number" name="buffer_after_minutes" min="0" max="1440" value="0" required></label>
                <button type="submit">ثبت خدمت غیرفعال</button>
            </form>
        @endif
    </section>

    <section>
        <h2>پزشکان</h2>
        <ul>@foreach ($practitioners as $practitioner)
            <li>
                {{ $practitioner->display_name_fa }} - {{ $practitioner->is_active ? 'فعال' : 'غیرفعال' }}
                @if ($canManage)
                    <form method="post" action="{{ route('staff.configuration.practitioners.update', $practitioner) }}">@csrf @method('PATCH')
                        <input type="hidden" name="version" value="{{ $practitioner->version }}">
                        <label>نام نمایشی <input name="display_name_fa" value="{{ $practitioner->display_name_fa }}" maxlength="200" required></label>
                        <input type="hidden" name="is_active" value="0">
                        <label>فعال <input type="checkbox" name="is_active" value="1" @checked($practitioner->is_active)></label>
                        <button type="submit">ذخیره</button>
                    </form>
                @endif
            </li>
        @endforeach</ul>
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.practitioners.store') }}">@csrf
                <label>نام نمایشی پزشک <input name="display_name_fa" maxlength="200" required></label>
                <button type="submit">ثبت پزشک غیرفعال</button>
            </form>
        @endif
    </section>

    <section>
        <h2>منابع کلینیک</h2>
        <ul>@foreach ($resources as $resource)
            <li>
                {{ $resource->name_fa }} ({{ $resource->kind }}) - {{ $resource->is_active ? 'فعال' : 'غیرفعال' }}
                @if ($canManage)
                    <form method="post" action="{{ route('staff.configuration.resources.update', $resource) }}">@csrf @method('PATCH')
                        <input type="hidden" name="version" value="{{ $resource->version }}">
                        <label>نام <input name="name_fa" value="{{ $resource->name_fa }}" maxlength="200" required></label>
                        <label>نوع <input name="kind" value="{{ $resource->kind }}" maxlength="80" required></label>
                        <input type="hidden" name="is_active" value="0">
                        <label>فعال <input type="checkbox" name="is_active" value="1" @checked($resource->is_active)></label>
                        <button type="submit">ذخیره</button>
                    </form>
                @endif
            </li>
        @endforeach</ul>
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.resources.store') }}">@csrf
                <label>نام منبع <input name="name_fa" maxlength="200" required></label>
                <label>نوع منبع <input name="kind" maxlength="80" placeholder="placeholder — owner to confirm" required></label>
                <button type="submit">ثبت منبع غیرفعال</button>
            </form>
        @endif
    </section>

    <section>
        <h2>ساعات کلینیک</h2>
        <ul>@foreach ($clinicHours as $hour)<li>{{ $weekdays[$hour->weekday] ?? $hour->weekday }}: {{ substr($hour->start_local, 0, 5) }} تا {{ substr($hour->end_local, 0, 5) }} ({{ $hour->timezone }})</li>@endforeach</ul>
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.clinic-hours.store') }}">@csrf
                <label>روز <select name="weekday" required>@foreach ($weekdays as $number => $label)<option value="{{ $number }}">{{ $label }}</option>@endforeach</select></label>
                <label>شروع <input type="time" name="start_local" required></label>
                <label>پایان <input type="time" name="end_local" required></label>
                <input type="hidden" name="timezone" value="Asia/Tehran">
                <button type="submit">ثبت ساعت</button>
            </form>
        @endif
    </section>

    <section>
        <h2>ساعات پزشکان</h2>
        <ul>@foreach ($workingHours as $hour)<li>{{ $hour->practitioner?->display_name_fa }} - {{ $weekdays[$hour->weekday] ?? $hour->weekday }}: {{ substr($hour->start_local, 0, 5) }} تا {{ substr($hour->end_local, 0, 5) }}</li>@endforeach</ul>
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.working-hours.store') }}">@csrf
                <label>پزشک <select name="practitioner_id" required>@foreach ($practitioners as $practitioner)<option value="{{ $practitioner->id }}">{{ $practitioner->display_name_fa }}</option>@endforeach</select></label>
                <label>روز <select name="weekday" required>@foreach ($weekdays as $number => $label)<option value="{{ $number }}">{{ $label }}</option>@endforeach</select></label>
                <label>شروع <input type="time" name="start_local" required></label><label>پایان <input type="time" name="end_local" required></label>
                <button type="submit">ثبت ساعات پزشک</button>
            </form>
        @endif
    </section>

    <section>
        <h2>وقفه‌ها</h2>
        <ul>@foreach ($breaks as $break)<li>{{ $break->practitioner?->display_name_fa ?? 'کلینیک' }} - {{ $weekdays[$break->weekday] ?? $break->weekday }}: {{ substr($break->start_local, 0, 5) }} تا {{ substr($break->end_local, 0, 5) }}</li>@endforeach</ul>
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.breaks.store') }}">@csrf
                <label>پزشک (خالی = کلینیک) <select name="practitioner_id"><option value="">کلینیک</option>@foreach ($practitioners as $practitioner)<option value="{{ $practitioner->id }}">{{ $practitioner->display_name_fa }}</option>@endforeach</select></label>
                <label>روز <select name="weekday" required>@foreach ($weekdays as $number => $label)<option value="{{ $number }}">{{ $label }}</option>@endforeach</select></label>
                <label>شروع <input type="time" name="start_local" required></label><label>پایان <input type="time" name="end_local" required></label>
                <button type="submit">ثبت وقفه</button>
            </form>
        @endif
    </section>

    <section>
        <h2>تعطیلی‌ها و استثناهای برنامه</h2>
        <ul>@foreach ($exceptions as $exception)<li>{{ $exception->practitioner?->display_name_fa ?? 'کلینیک' }}: {{ $exception->starts_at->setTimezone('Asia/Tehran')->format('Y-m-d H:i') }} تا {{ $exception->ends_at->setTimezone('Asia/Tehran')->format('Y-m-d H:i') }} - {{ $exception->kind }}</li>@endforeach</ul>
        @if ($canManage)
            <form method="post" action="{{ route('staff.configuration.exceptions.store') }}">@csrf
                <label>پزشک (خالی = کلینیک) <select name="practitioner_id"><option value="">کلینیک</option>@foreach ($practitioners as $practitioner)<option value="{{ $practitioner->id }}">{{ $practitioner->display_name_fa }}</option>@endforeach</select></label>
                <label>شروع به وقت تهران <input type="datetime-local" name="starts_at" required></label><label>پایان به وقت تهران <input type="datetime-local" name="ends_at" required></label>
                <label>نوع <select name="kind" required><option value="closed">تعطیلی</option><option value="holiday">تعطیلات</option><option value="special_opening">ساعات ویژه</option></select></label>
                <button type="submit">ثبت استثنا</button>
            </form>
        @endif
    </section>
</main>
</body>
</html>
