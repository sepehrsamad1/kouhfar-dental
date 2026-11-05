<?php

declare(strict_types=1);

use App\AuditLog;
use App\DeletionRequest;
use App\Http\Middleware\EnsureStaffSessionIsFresh;
use App\Patient;
use App\Services\DeletionRequestWorkflow;
use App\StaffUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function deletionWorkflowAdmin(): StaffUser
{
    return StaffUser::factory()->active()->create(['role' => 'admin']);
}

it('moves a pending request through review and closes it with an audited non-destructive outcome', function (): void {
    $admin = deletionWorkflowAdmin();
    $request = DeletionRequest::factory()->create();

    $reviewed = app(DeletionRequestWorkflow::class)->review($request, $admin, 'accept_for_assessment');

    expect($reviewed->status)->toBe('under_review')
        ->and($reviewed->reviewed_by)->toBe($admin->getKey())
        ->and($reviewed->reviewed_at)->not->toBeNull()
        ->and($reviewed->approved_by)->toBeNull();

    $closed = app(DeletionRequestWorkflow::class)->close($reviewed, $admin, 'retained_by_policy');

    expect($closed->status)->toBe('closed')
        ->and($closed->resolved_at)->not->toBeNull()
        ->and($closed->resolution_code)->toBe('retained_by_policy')
        ->and(AuditLog::query()->where('action', 'deletion_request.reviewed')->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'deletion_request.closed')->count())->toBe(1);
});

it('rejects inactive and non-admin actors', function (): void {
    $request = DeletionRequest::factory()->create();
    $workflow = app(DeletionRequestWorkflow::class);

    expect(fn () => $workflow->review($request, StaffUser::factory()->active()->create(['role' => 'reception']), 'accept_for_assessment'))
        ->toThrow(AuthorizationException::class);

    $inactiveAdmin = StaffUser::factory()->create(['role' => 'admin', 'is_active' => false]);

    expect(fn () => $workflow->review($request, $inactiveAdmin, 'accept_for_assessment'))
        ->toThrow(AuthorizationException::class);
});

it('requires allowlisted rejection and resolution codes', function (): void {
    $admin = deletionWorkflowAdmin();
    $request = DeletionRequest::factory()->create();
    $workflow = app(DeletionRequestWorkflow::class);

    expect(fn () => $workflow->review($request, $admin, 'reject'))
        ->toThrow(InvalidArgumentException::class);

    $reviewed = $workflow->review($request, $admin, 'accept_for_assessment');

    expect(fn () => $workflow->close($reviewed, $admin, 'delete_everything'))
        ->toThrow(InvalidArgumentException::class);
});

it('prevents repeated or out-of-order transitions', function (): void {
    $admin = deletionWorkflowAdmin();
    $request = DeletionRequest::factory()->create();
    $workflow = app(DeletionRequestWorkflow::class);

    $reviewed = $workflow->review($request, $admin, 'accept_for_assessment');

    expect(fn () => $workflow->review($reviewed, $admin, 'accept_for_assessment'))
        ->toThrow(LogicException::class);

    $pending = DeletionRequest::factory()->create();

    expect(fn () => $workflow->close($pending, $admin, 'retained_by_policy'))
        ->toThrow(LogicException::class);
});

it('does not delete or mutate the referenced patient record when a request is closed', function (): void {
    $admin = deletionWorkflowAdmin();
    $patient = Patient::factory()->create();
    $request = DeletionRequest::factory()->create([
        'target_type' => 'Patient',
        'target_id' => $patient->getKey(),
    ]);
    $workflow = app(DeletionRequestWorkflow::class);

    $reviewed = $workflow->review($request, $admin, 'accept_for_assessment');
    $workflow->close($reviewed, $admin, 'retained_by_policy');

    expect(Patient::query()->whereKey($patient->getKey())->exists())->toBeTrue();
});

it('exposes review and close actions only through authenticated staff routes', function (): void {
    $this->withoutMiddleware(EnsureStaffSessionIsFresh::class);
    $admin = deletionWorkflowAdmin();
    $request = DeletionRequest::factory()->create();

    $this->actingAs($admin, 'staff')
        ->postJson(route('staff.deletion-requests.review', $request), [
            'decision' => 'accept_for_assessment',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'under_review');

    $this->actingAs($admin, 'staff')
        ->postJson(route('staff.deletion-requests.close', $request), [
            'resolution_code' => 'retained_by_policy',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'closed');
});

it('denies deletion request workflow endpoints to reception staff', function (): void {
    $this->withoutMiddleware(EnsureStaffSessionIsFresh::class);
    $reception = StaffUser::factory()->active()->create(['role' => 'reception']);
    $request = DeletionRequest::factory()->create();

    $this->actingAs($reception, 'staff')
        ->postJson(route('staff.deletion-requests.review', $request), [
            'decision' => 'accept_for_assessment',
        ])
        ->assertForbidden();

    expect($request->refresh()->status)->toBe('pending');
});
