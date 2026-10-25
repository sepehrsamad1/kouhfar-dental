<?php

use App\Appointment;
use App\AppointmentEvent;
use App\AuditLog;
use App\ContentItem;
use App\FollowUpToken;
use App\LoginAttempt;
use App\MediaAsset;
use App\Patient;
use App\Practitioner;
use App\Resource;
use App\ScheduleException;
use App\Service;
use App\Session;
use App\WorkingHour;

it('configures all domain models to use non-incrementing string UUID keys', function () {
    $modelClasses = [
        Patient::class,
        Service::class,
        Practitioner::class,
        Resource::class,
        Appointment::class,
        AppointmentEvent::class,
        FollowUpToken::class,
        Session::class,
        LoginAttempt::class,
        WorkingHour::class,
        ScheduleException::class,
        AuditLog::class,
        ContentItem::class,
        MediaAsset::class,
    ];

    foreach ($modelClasses as $modelClass) {
        $model = new $modelClass;

        expect($model->getKeyType())
            ->toBe('string')
            ->and($model->getIncrementing())->toBeFalse();
    }
});
