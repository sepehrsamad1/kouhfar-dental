<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نمایش موبایل بیمار</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main>
    <h1>شماره موبایل بیمار</h1>
    <p>این شماره فقط پس از احراز مجوز و ثبت رویداد حسابرسی نمایش داده شده است.</p>
    <p dir="ltr">{{ $mobile }}</p>
    <a href="{{ route('staff.patients.index') }}">بازگشت به بیماران</a>
</main>
</body>
</html>
