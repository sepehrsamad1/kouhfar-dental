<?php

use App\StaffUser;

it('allows only the planned staff user fields', function () {
    expect((new StaffUser)->getFillable())->toBe([
        'email',
        'display_name',
        'role',
        'is_active',
    ]);
});

it('hides and hashes the password hash and casts the active flag', function () {
    $staffUser = new StaffUser;

    expect($staffUser->getHidden())->toContain('password_hash')
        ->and($staffUser->getCasts()['password_hash'])->toBe('hashed')
        ->and($staffUser->getCasts()['is_active'])->toBe('boolean');
});

it('makes synthetic inactive staff data without saving it', function () {
    $staffUser = StaffUser::factory()->make();

    expect($staffUser->exists)->toBeFalse()
        ->and($staffUser->role)->toBe('synthetic-test-only')
        ->and($staffUser->is_active)->toBeFalse();
});
