@extends('layouts.staff')

@section('title', 'بیماران')

@section('content')
<main>
    <h1>بیماران</h1>

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    @if (session('duplicate_warning'))
        <p role="alert">
            هشدار: {{ session('duplicate_count') }} بیمار با این شماره موبایل پیدا شد.
            ثبت خودکار یا ادغام انجام نمی‌شود؛ تأیید صریح لازم است.
        </p>
    @endif

    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="get" action="{{ route('staff.patients.index') }}">
        <label for="q">جستجو</label>
        <input id="q" name="q" value="{{ request('q') }}" maxlength="100">
        <label>
            <input type="checkbox" name="include_archived" value="1" @checked(request()->boolean('include_archived'))>
            نمایش بایگانی‌شده‌ها
        </label>
        <button type="submit">جستجو</button>
    </form>

    @if ($canCreate)
        <h2>ثبت بیمار</h2>
        <form method="post" action="{{ route('staff.patients.store') }}">
            @csrf
            <label for="full_name">نام و نام خانوادگی</label>
            <input id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="200" required>

            <label for="mobile">موبایل</label>
            <input id="mobile" name="mobile" value="{{ old('mobile') }}" maxlength="40" required inputmode="tel">

            @if (session('duplicate_warning'))
                <label>
                    <input type="checkbox" name="confirm_duplicate" value="1">
                    می‌دانم شماره تکراری است و ثبت بیمار جدید را تأیید می‌کنم.
                </label>
            @endif

            <button type="submit">ثبت بیمار</button>
        </form>
    @endif

    <table>
        <thead>
        <tr>
            <th>نام</th>
            <th>موبایل</th>
            <th>وضعیت</th>
            <th>عملیات</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($patients as $patient)
            <tr>
                <td>{{ $patient['full_name'] }}</td>
                <td>
                    {{ $patient['mobile'] }}
                    <a href="{{ route('staff.patients.reveal-mobile', $patient['id']) }}">نمایش شماره</a>
                </td>
                <td>{{ $patient['archived_at'] ? 'بایگانی‌شده' : 'فعال' }}</td>
                <td>
                    @can('update', \App\Patient::find($patient['id']))
                        <form method="post" action="{{ route('staff.patients.update', $patient['id']) }}">
                            @csrf
                            @method('PATCH')
                            <input name="full_name" value="{{ $patient['full_name'] }}" maxlength="200" required>
                            <input name="mobile" value="" placeholder="در صورت تغییر، شماره جدید" maxlength="40" inputmode="tel">
                            <input type="hidden" name="version" value="{{ $patient['version'] }}">
                            <label>
                                <input type="checkbox" name="confirm_duplicate" value="1">
                                اگر شماره متعلق به بیمار دیگری است، استفاده از شماره تکراری را تأیید می‌کنم.
                            </label>
                            <button type="submit">ذخیره</button>
                        </form>
                    @endcan
                    @can('archive', \App\Patient::find($patient['id']))
                        @if (! $patient['archived_at'])
                            <form method="post" action="{{ route('staff.patients.archive', $patient['id']) }}">
                                @csrf
                                <button type="submit">بایگانی</button>
                            </form>
                        @endif
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4">بیماری پیدا نشد.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $patients->links() }}
</main>
@endsection
