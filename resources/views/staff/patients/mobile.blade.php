@extends('layouts.staff')

@section('title', 'نمایش موبایل بیمار')

@section('content')
<main>
    <h1>شماره موبایل بیمار</h1>
    <p>این شماره فقط پس از احراز مجوز و ثبت رویداد حسابرسی نمایش داده شده است.</p>
    <p dir="ltr">{{ $mobile }}</p>
    <a href="{{ route('staff.patients.index') }}">بازگشت به بیماران</a>
</main>
@endsection
