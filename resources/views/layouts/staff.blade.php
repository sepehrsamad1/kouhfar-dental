<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'پورتال پرسنل') | {{ config('app.name') }}</title>
    @vite(['resources/css/staff.css', 'resources/js/staff.js'])
</head>
<body class="staff-shell">
    <nav class="staff-nav" aria-label="ناوبری پرسنل">
        <div class="staff-nav__inner">
            <div class="staff-nav__links">
                <a class="staff-nav__brand" href="{{ route('staff.dashboard') }}">داشبورد پرسنل</a>
                <a href="{{ route('staff.patients.index') }}">بیماران</a>
                @can('manage-configuration')
                    <a href="{{ route('staff.configuration.index') }}">تنظیمات کلینیک</a>
                @endcan
                @can('view-audit-log')
                    <a href="{{ route('staff.admin.audit') }}">گزارش حسابرسی</a>
                @endcan
            </div>

            <div class="staff-nav__actions">
                <span class="staff-nav__user">{{ auth('staff')->user()?->display_name ?? 'Staff' }}</span>
                <form method="post" action="{{ route('staff.logout') }}">
                    @csrf
                    <button class="staff-nav__logout" type="submit">خروج</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="staff-main">
        @if (session('success'))
            <div class="staff-alert staff-alert--status" role="status">{{ session('success') }}</div>
        @endif

        @if (session('status'))
            <div class="staff-alert staff-alert--status" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="staff-alert staff-alert--error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
