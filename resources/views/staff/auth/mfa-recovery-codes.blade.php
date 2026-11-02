<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>کدهای بازیابی | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>کدهای بازیابی یک‌بارمصرف</h1>
        <p role="alert">این کدها فقط همین یک بار نمایش داده می‌شوند. آن‌ها را در محل امن نگهداری کنید. هر کد پس از یک بار استفاده باطل می‌شود.</p>
        <ul>
            @foreach ($codes as $code)
                <li><code dir="ltr">{{ $code }}</code></li>
            @endforeach
        </ul>
        <a href="{{ route('staff.dashboard') }}">ادامه به پنل کارکنان</a>
    </main>
</body>
</html>
