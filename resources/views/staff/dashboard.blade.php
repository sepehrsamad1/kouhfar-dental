<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پنل کارکنان | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>پنل کارکنان</h1>
        <p>{{ auth('staff')->user()->display_name }}</p>
        <form method="post" action="{{ route('staff.logout') }}">
            @csrf
            <button type="submit">خروج</button>
        </form>
    </main>
</body>
</html>
