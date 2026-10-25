<?php

use App\Appointment;
use App\Practitioner;
use App\Resource;
use App\Service;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines the planned practitioner service and appointment relationships', function () {
    $practitioner = new Practitioner;

    expect($practitioner->services())
        ->toBeInstanceOf(BelongsToMany::class)
        ->and($practitioner->services()->getTable())->toBe('practitioner_services')
        ->and($practitioner->services()->getRelated())->toBeInstanceOf(Service::class)
        ->and($practitioner->appointments())
        ->toBeInstanceOf(BelongsToMany::class)
        ->and($practitioner->appointments()->getTable())->toBe('appointment_practitioners')
        ->and($practitioner->appointments()->getRelated())->toBeInstanceOf(Appointment::class);
});

it('defines the planned service practitioner resource and appointment relationships', function () {
    $service = new Service;

    expect($service->practitioners())
        ->toBeInstanceOf(BelongsToMany::class)
        ->and($service->practitioners()->getTable())->toBe('practitioner_services')
        ->and($service->practitioners()->getRelated())->toBeInstanceOf(Practitioner::class)
        ->and($service->resources())
        ->toBeInstanceOf(BelongsToMany::class)
        ->and($service->resources()->getTable())->toBe('service_resources')
        ->and($service->resources()->getRelated())->toBeInstanceOf(Resource::class)
        ->and($service->appointments())
        ->toBeInstanceOf(HasMany::class)
        ->and($service->appointments()->getRelated())->toBeInstanceOf(Appointment::class);
});

it('defines the planned resource service and appointment relationships', function () {
    $resource = new Resource;

    expect($resource->services())
        ->toBeInstanceOf(BelongsToMany::class)
        ->and($resource->services()->getTable())->toBe('service_resources')
        ->and($resource->services()->getRelated())->toBeInstanceOf(Service::class)
        ->and($resource->appointments())
        ->toBeInstanceOf(BelongsToMany::class)
        ->and($resource->appointments()->getTable())->toBe('appointment_resources')
        ->and($resource->appointments()->getRelated())->toBeInstanceOf(Appointment::class);
});
