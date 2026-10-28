@extends('layouts.staff')

@section('title', 'تقویم نوبت‌ها')

@section('content')
<section class="staff-calendar" aria-labelledby="appointments-heading">
    <div class="staff-calendar__header">
        <div>
            <h1 id="appointments-heading">تقویم نوبت‌ها</h1>
            <p class="staff-calendar__timezone">زمان‌ها به وقت محلی کلینیک (Asia/Tehran) نمایش داده می‌شوند.</p>
        </div>
        <nav class="staff-calendar__views" aria-label="نوع نمایش تقویم">
            <a href="{{ route('staff.appointments.index', ['view' => 'day', 'date' => $date->format('Y-m-d')]) }}" @class(['is-active' => $view === 'day'])>روز</a>
            <a href="{{ route('staff.appointments.index', ['view' => 'week', 'date' => $date->format('Y-m-d')]) }}" @class(['is-active' => $view === 'week'])>هفته</a>
            <a href="{{ route('staff.appointments.index', ['view' => 'list', 'date' => $date->format('Y-m-d')]) }}" @class(['is-active' => $view === 'list'])>فهرست دسترس‌پذیر</a>
        </nav>
    </div>

    <div class="staff-calendar__navigation" aria-label="جابجایی تاریخ">
        <a href="{{ route('staff.appointments.index', ['view' => $view, 'date' => ($view === 'week' ? $rangeStartLocal->subWeek() : $date->subDay())->format('Y-m-d')]) }}">بازه قبل</a>
        <strong>{{ $rangeStartLocal->format('Y/m/d') }}@if ($view === 'week') تا {{ $rangeEndLocal->subDay()->format('Y/m/d') }}@endif</strong>
        <a href="{{ route('staff.appointments.index', ['view' => $view, 'date' => ($view === 'week' ? $rangeStartLocal->addWeek() : $date->addDay())->format('Y-m-d')]) }}">بازه بعد</a>
    </div>

    @if ($view === 'week')
        <div class="staff-calendar__week" aria-label="تقویم هفتگی">
            @foreach (range(0, 6) as $offset)
                @php($day = $rangeStartLocal->addDays($offset))
                <section class="staff-calendar__day" aria-labelledby="day-{{ $offset }}">
                    <h2 id="day-{{ $offset }}">{{ $day->format('Y/m/d') }}</h2>
                    @forelse ($appointments->filter(fn ($appointment) => $appointment->start_at->setTimezone('Asia/Tehran')->isSameDay($day)) as $appointment)
                        @include('staff.appointments._card', ['appointment' => $appointment, 'canSchedule' => $canSchedule, 'isPractitioner' => $isPractitioner])
                    @empty
                        <p class="staff-calendar__empty">نوبتی ثبت نشده است.</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    @elseif ($view === 'day')
        <div class="staff-calendar__day-list" aria-label="نوبت‌های روز">
            @forelse ($appointments as $appointment)
                @include('staff.appointments._card', ['appointment' => $appointment, 'canSchedule' => $canSchedule, 'isPractitioner' => $isPractitioner])
            @empty
                <p class="staff-calendar__empty">نوبتی برای این روز ثبت نشده است.</p>
            @endforelse
        </div>
    @else
        <div class="staff-calendar__table-wrap">
            <table class="staff-calendar__table">
                <caption>فهرست قابل دسترس نوبت‌ها برای بازه انتخاب‌شده</caption>
                <thead><tr><th scope="col">تاریخ و زمان</th><th scope="col">بیمار</th><th scope="col">خدمت</th><th scope="col">وضعیت</th><th scope="col">نسخه</th><th scope="col">عملیات</th></tr></thead>
                <tbody>
                @forelse ($appointments as $appointment)
                    <tr>
                        <td>{{ $appointment->start_at->setTimezone('Asia/Tehran')->format('Y/m/d H:i') }} تا {{ $appointment->end_at->setTimezone('Asia/Tehran')->format('H:i') }}</td>
                        <td>{{ $appointment->patient->full_name }}</td>
                        <td>{{ $appointment->service->name_fa }}</td>
                        <td>{{ $appointment->status->value }}</td>
                        <td>{{ $appointment->version }}</td>
                        <td>@include('staff.appointments._actions', ['appointment' => $appointment, 'canSchedule' => $canSchedule, 'isPractitioner' => $isPractitioner])</td>
                    </tr>
                @empty
                    <tr><td colspan="6">نوبتی برای این بازه ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <p class="staff-calendar__announcement" role="status" aria-live="polite" id="calendar-status">تغییرات با پاسخ سرور اعلام می‌شوند؛ در صورت تغییر هم‌زمان، صفحه را تازه‌سازی کنید.</p>
</section>
@endsection
