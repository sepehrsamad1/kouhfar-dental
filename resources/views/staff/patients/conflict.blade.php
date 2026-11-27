@extends('layouts.staff')

@section('title', 'تعارض در به‌روزرسانی بیمار')

@section('content')
<main>
    <h1>اطلاعات بیمار تغییر کرده است</h1>

    <p role="alert">
        {{ $message }}
    </p>

    <p>
        برای جلوگیری از بازنویسی اطلاعات جدید، تغییرات شما ذخیره نشد.
    </p>

    <a href="{{ route('staff.patients.index') }}">
        بازگشت به فهرست بیماران
    </a>
</main>
@endsection
