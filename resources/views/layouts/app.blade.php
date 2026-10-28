<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>کلینیک دندانپزشکی دیجیتال — دکتر آرش کوهفر</title>
    <meta name="description" content="کلینیک دندانپزشکی دیجیتال دکتر آرش کوهفر — درمان دقیق، زیبایی طبیعی، تجربه‌ای متفاوت از دندانپزشکی.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @yield('content')
</body>
</html>
