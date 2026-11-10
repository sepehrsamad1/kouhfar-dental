<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>راه‌اندازی تأیید دومرحله‌ای | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>راه‌اندازی تأیید دومرحله‌ای</h1>
        <p>این حساب کارمندی باید با یک برنامه احراز هویت سازگار با TOTP محافظت شود.</p>
        <ol>
            <li>در برنامه‌ای مانند Google Authenticator، Microsoft Authenticator یا 1Password یک حساب جدید اضافه کنید.</li>
            <li>کلید زیر را به‌صورت دستی وارد کنید. این کلید را در اختیار هیچ‌کس قرار ندهید.</li>
            <li>کد شش‌رقمی برنامه را برای فعال‌سازی وارد کنید.</li>
        </ol>
        <p><strong>کلید راه‌اندازی:</strong></p>
        <code dir="ltr">{{ $secret }}</code>
        <p>حساب: {{ auth('staff')->user()?->email ?? 'حساب کارکنان' }}</p>
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('staff.mfa.setup.complete') }}">
            @csrf
            <label for="code">کد شش‌رقمی برنامه احراز هویت</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
            <button type="submit">فعال‌سازی و ورود</button>
        </form>
    </main>
</body>
</html>
