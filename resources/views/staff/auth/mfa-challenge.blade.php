<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأیید دومرحله‌ای | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>تأیید دومرحله‌ای</h1>
        <p>کد برنامه احراز هویت یا یکی از کدهای بازیابی یک‌بارمصرف را وارد کنید.</p>
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('staff.mfa.verify') }}">
            @csrf
            <label for="code">کد تأیید یا کد بازیابی</label>
            <input id="code" name="code" type="text" inputmode="text" autocomplete="one-time-code" maxlength="64" required autofocus>
            <button type="submit">تأیید و ورود</button>
        </form>
    </main>
</body>
</html>
