<div class="staff-calendar__actions" aria-label="عملیات نوبت">
    @if ($canSchedule && $appointment->status->value === 'pending')
        <form method="post" action="{{ route('staff.appointments.confirm', $appointment) }}">
            @csrf
            <input type="hidden" name="version" value="{{ $appointment->version }}">
            <button type="submit">تأیید</button>
        </form>
    @endif
    @if ($canSchedule && in_array($appointment->status->value, ['pending', 'confirmed'], true))
        <form method="post" action="{{ route('staff.appointments.cancel', $appointment) }}">
            @csrf
            <input type="hidden" name="version" value="{{ $appointment->version }}">
            <button type="submit">لغو</button>
        </form>
    @endif
    @if ($canSchedule && $appointment->status->value === 'confirmed')
        <form method="post" action="{{ route('staff.appointments.reschedule', $appointment) }}" class="staff-calendar__reschedule">
            @csrf
            @method('PATCH')
            <input type="hidden" name="version" value="{{ $appointment->version }}">
            <label>زمان جدید
                <input type="datetime-local" name="start_at" value="{{ $appointment->start_at->setTimezone('Asia/Tehran')->format('Y-m-d\TH:i') }}" required>
            </label>
            <button type="submit">تغییر زمان</button>
        </form>
    @endif
    @if ($appointment->status->value === 'confirmed' && ($canSchedule || $isPractitioner))
        <form method="post" action="{{ route('staff.appointments.complete', $appointment) }}">
            @csrf
            <input type="hidden" name="version" value="{{ $appointment->version }}">
            <button type="submit">تکمیل ویزیت</button>
        </form>
        <form method="post" action="{{ route('staff.appointments.no-show', $appointment) }}">
            @csrf
            <input type="hidden" name="version" value="{{ $appointment->version }}">
            <button type="submit">عدم مراجعه</button>
        </form>
    @endif
</div>
