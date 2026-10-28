@extends('layouts.staff')

@section('title', 'داشبورد پرسنل')

@section('content')
    <h1>پنل کارکنان</h1>
    <p>{{ auth('staff')->user()->display_name }}</p>

    <nav aria-label="عملیات پرسنل">
        <p><a href="{{ route('staff.patients.index') }}">مدیریت بیماران</a></p>
        @can('manage-configuration')
            <p><a href="{{ route('staff.configuration.index') }}">تنظیمات کلینیک</a></p>
        @endcan
        @can('view-audit-log')
            <p><a href="{{ route('staff.admin.audit') }}">گزارش حسابرسی</a></p>
        @endcan
    </nav>
@endsection
