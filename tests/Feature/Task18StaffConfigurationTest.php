<?php

declare(strict_types=1);

use App\ClinicWorkingHour;
use App\Practitioner;
use App\Resource;
use App\ScheduleBreak;
use App\ScheduleException;
use App\Service;
use App\StaffUser;
use App\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function task18AuthenticateStaff(string $role = 'admin'): StaffUser
{
    $staff = StaffUser::factory()->active()->create([
        'role' => $role,
    ]);

    $staff->forceFill([
        'password_hash' => Hash::make('task18-password'),
    ])->save();

    test()->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'task18-password',
    ])->assertRedirect();

    return $staff;
}
it('renders complete staff configuration create and update/delete forms with current versions', function (): void {
    task18AuthenticateStaff();

    $service = Service::factory()->create(['version' => 1]);
    $practitioner = Practitioner::factory()->create(['version' => 1]);
    $practitioner->services()->sync([$service->id]);
    $resource = Resource::factory()->create(['version' => 1]);
    $clinicHour = ClinicWorkingHour::query()->create([
        'version' => 1,
        'weekday' => 1,
        'start_local' => '09:00',
        'end_local' => '10:00',
        'timezone' => 'Asia/Tehran',
    ]);
    $workingHour = WorkingHour::factory()->create([
        'version' => 1,
        'practitioner_id' => $practitioner->id,
    ]);
    $break = ScheduleBreak::query()->create([
        'version' => 1,
        'practitioner_id' => $practitioner->id,
        'weekday' => 1,
        'start_local' => '10:00',
        'end_local' => '10:15',
        'timezone' => 'Asia/Tehran',
    ]);
    $exception = ScheduleException::factory()->create([
        'version' => 1,
        'practitioner_id' => $practitioner->id,
    ]);

    $response = test()->get(route('staff.configuration.index'));

    $response->assertOk()
        ->assertSee(route('staff.configuration.services.store'))
        ->assertSee(route('staff.configuration.services.update', $service))
        ->assertSee('name="version" value="'.$service->version.'"', false)
        ->assertSee(route('staff.configuration.practitioners.store'))
        ->assertSee(route('staff.configuration.practitioners.update', $practitioner))
        ->assertSee(route('staff.configuration.practitioners.services.update', $practitioner))
        ->assertSee('name="service_ids[]"', false)
        ->assertSee(route('staff.configuration.resources.store'))
        ->assertSee(route('staff.configuration.resources.update', $resource))
        ->assertSee(route('staff.configuration.clinic-hours.store'))
        ->assertSee(route('staff.configuration.clinic-hours.update', $clinicHour))
        ->assertSee(route('staff.configuration.clinic-hours.delete', $clinicHour))
        ->assertSee(route('staff.configuration.working-hours.store'))
        ->assertSee(route('staff.configuration.working-hours.update', $workingHour))
        ->assertSee(route('staff.configuration.working-hours.delete', $workingHour))
        ->assertSee(route('staff.configuration.breaks.store'))
        ->assertSee(route('staff.configuration.breaks.update', $break))
        ->assertSee(route('staff.configuration.breaks.delete', $break))
        ->assertSee(route('staff.configuration.exceptions.store'))
        ->assertSee(route('staff.configuration.exceptions.update', $exception))
        ->assertSee(route('staff.configuration.exceptions.delete', $exception));
});

it('keeps patient update aligned with PatientUpdateRequest by submitting mobile, not a client-normalized field', function (): void {
    task18AuthenticateStaff();

    $response = test()->get(route('staff.patients.index'));

    $response->assertOk()
        ->assertSee('name="mobile"', false)
        ->assertDontSee('name="mobile_normalized"', false)
        ->assertSee('بدون تغییر؛ برای تغییر شماره، شماره جدید را وارد کنید');
});
