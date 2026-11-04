<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود کارکنان | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>ورود کارکنان</h1>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('staff.login.store') }}">
            @csrf
            <label for="email">ایمیل</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
            <label for="password">رمز عبور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">ورود</button>
        </form>

        <hr>
        <h2>ورود با کد پیامکی</h2>
        <form method="post" action="{{ route('staff.login.otp.send') }}">
            @csrf
            <label for="staff-mobile">شماره موبایل ثبت‌شده برای حساب کارکنان</label>
            <input id="staff-mobile" name="mobile" type="tel" inputmode="tel" autocomplete="tel" required>
            <button type="submit">ارسال کد ورود</button>
        </form>
        <form method="post" action="{{ route('staff.login.otp.verify') }}">
            @csrf
            <label for="staff-otp">کد پیامکی</label>
            <input id="staff-otp" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required>
            <button type="submit">ورود با کد</button>
        </form>
    </main>
</body>
</html>
