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
    </main>
</body>
</html>
