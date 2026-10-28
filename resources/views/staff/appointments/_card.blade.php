<article class="staff-calendar__card">
    <header>
        <strong>{{ $appointment->start_at->setTimezone('Asia/Tehran')->format('H:i') }}–{{ $appointment->end_at->setTimezone('Asia/Tehran')->format('H:i') }}</strong>
        <span class="staff-calendar__status">{{ $appointment->status->value }}</span>
    </header>
    <p><strong>بیمار:</strong> {{ $appointment->patient->full_name }}</p>
    <p><strong>خدمت:</strong> {{ $appointment->service->name_fa }}</p>
    <p><strong>نسخه:</strong> {{ $appointment->version }}</p>
    @include('staff.appointments._actions', ['appointment' => $appointment, 'canSchedule' => $canSchedule, 'isPractitioner' => $isPractitioner])
</article>
