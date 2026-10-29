<?php

use App\Appointment;
use App\Jobs\PurgeMediaAsset;
use App\MediaAsset;
use App\Patient;
use App\Services\MediaAssetService;
use App\StaffUser;
use App\Http\Middleware\EnsureStaffSessionIsFresh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class)->beforeEach(function (): void {
    $this->withoutMiddleware(EnsureStaffSessionIsFresh::class);
});

function validWebpUpload(): UploadedFile
{
    $image = imagecreatetruecolor(2, 2);
    $path = tempnam(sys_get_temp_dir(), 'kouhfar-test-webp-');

    if ($path === false || ! imagewebp($image, $path, 80)) {
        imagedestroy($image);
        test()->markTestSkipped('WebP encoding is unavailable in the test environment.');
    }

    imagedestroy($image);
    $bytes = file_get_contents($path);
    unlink($path);

    return UploadedFile::fake()->createWithContent('client-supplied.webp', $bytes === false ? '' : $bytes);
}

function validPngUpload(): UploadedFile
{
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        true
    );

    return UploadedFile::fake()->createWithContent('patient.png', $png);
}

function jpegUploadWithExifMarker(): UploadedFile
{
    $image = imagecreatetruecolor(2, 2);
    $path = tempnam(sys_get_temp_dir(), 'kouhfar-test-jpeg-');

    if ($path === false || ! imagejpeg($image, $path, 90)) {
        imagedestroy($image);
        test()->fail('Unable to create JPEG fixture.');
    }

    imagedestroy($image);
    $jpeg = file_get_contents($path);
    unlink($path);

    if ($jpeg === false || ! str_starts_with($jpeg, "\xFF\xD8")) {
        test()->fail('Unable to create a valid JPEG fixture.');
    }

    $exifPayload = "Exif\x00\x00".'KOUHFAR-SYNTHETIC-EXIF';
    $segmentLength = strlen($exifPayload) + 2;
    $app1 = "\xFF\xE1".pack('n', $segmentLength).$exifPayload;
    $withExif = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

    return UploadedFile::fake()->createWithContent('synthetic-exif.jpg', $withExif);
}

it('accepts a real png, stores it privately with a generated filename and returns a 15-minute signed url', function () {
    Storage::fake('media');

    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();

    $response = $this->actingAs($admin, 'staff')->post('/staff/media', [
        'media' => validPngUpload(),
        'kind' => 'smile_input',
    ]);

    $response->assertCreated()
        ->assertJsonPath('mime_type', 'image/png')
        ->assertJsonPath('byte_size', fn ($value) => $value > 0);

    $asset = MediaAsset::query()->firstOrFail();

    expect($asset->storage_key)
        ->toStartWith('media/')
        ->not->toContain('patient.png');

    Storage::disk('media')->assertExists($asset->storage_key);

    $url = $response->json('url');
    expect($url)->toContain('signature=')
        ->and($url)->toContain('expires=');

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('accepts WebP when the server image processor supports it', function () {
    Storage::fake('media');

    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();

    $response = $this->actingAs($admin, 'staff')->post('/staff/media', [
        'media' => validWebpUpload(),
        'kind' => 'other',
    ]);

    $response->assertCreated()->assertJsonPath('mime_type', 'image/webp');
    Storage::disk('media')->assertExists(MediaAsset::query()->firstOrFail()->storage_key);
});

it('re-encodes JPEG uploads and strips EXIF metadata', function () {
    Storage::fake('media');

    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();

    $response = $this->actingAs($admin, 'staff')->post('/staff/media', [
        'media' => jpegUploadWithExifMarker(),
        'kind' => 'case_before',
    ]);

    $response->assertCreated();

    $asset = MediaAsset::query()->firstOrFail();
    $processed = Storage::disk('media')->get($asset->storage_key);

    expect($processed)->not->toContain('KOUHFAR-SYNTHETIC-EXIF')
        ->and($processed)->not->toContain("Exif\x00\x00");
});

it('rejects a renamed executable or non-image payload despite an image extension', function () {
    Storage::fake('media');

    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();

    $response = $this->actingAs($admin, 'staff')->post('/staff/media', [
        'media' => UploadedFile::fake()->createWithContent('evil.png', "<?php echo 'not an image';"),
        'kind' => 'other',
    ]);

    $response->assertStatus(422);
    expect(MediaAsset::query()->count())->toBe(0);
});

it('rejects files larger than 10 MB', function () {
    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();

    $response = $this->actingAs($admin, 'staff')->post('/staff/media', [
        'media' => UploadedFile::fake()->create('large.jpg', 10_241, 'image/jpeg'),
        'kind' => 'other',
    ]);

    $response->assertStatus(422);
});

it('denies non-admin staff because no broader media role has been approved', function () {
    $reception = StaffUser::factory()->active()->state(['role' => 'reception'])->create();

    $response = $this->actingAs($reception, 'staff')->post('/staff/media', [
        'media' => validPngUpload(),
        'kind' => 'other',
    ]);

    $response->assertForbidden();
});

it('denies an unsigned download and an expired signed download', function () {
    Storage::fake('media');

    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();
    $asset = MediaAsset::factory()->create([
        'storage_key' => 'media/test.png',
        'mime_type' => 'image/png',
        'byte_size' => 67,
    ]);
    Storage::disk('media')->put($asset->storage_key, 'synthetic');

    $this->actingAs($admin, 'staff')
        ->get(route('staff.media.download', ['mediaAsset' => $asset->id]))
        ->assertForbidden();

    $expired = URL::temporarySignedRoute(
        'staff.media.download',
        now('UTC')->subMinute(),
        ['mediaAsset' => $asset->id],
    );

    $this->actingAs($admin, 'staff')->get($expired)->assertForbidden();
});

it('soft deletes early and queues the 30-day hard purge hook', function () {
    Queue::fake();
    Storage::fake('media');

    $admin = StaffUser::factory()->active()->state(['role' => 'admin'])->create();
    $asset = MediaAsset::factory()->create([
        'storage_key' => 'media/test.png',
        'mime_type' => 'image/png',
    ]);
    Storage::disk('media')->put($asset->storage_key, 'synthetic');

    $this->actingAs($admin, 'staff')
        ->delete(route('staff.media.delete', $asset))
        ->assertRedirect();

    expect($asset->fresh()->deleted_at)->not->toBeNull();
    Queue::assertPushed(PurgeMediaAsset::class, function (PurgeMediaAsset $job) use ($asset): bool {
        return $job->mediaAssetId === $asset->id;
    });
});

it('enforces the 90-day pre-consultation and 2-year clinical retention windows', function () {
    $service = app(MediaAssetService::class);
    $now = new DateTimeImmutable('2026-10-29 00:00:00', new DateTimeZone('UTC'));

    $preConsultation = MediaAsset::factory()->create([
        'created_at' => $now->modify('-91 days'),
        'owner_patient_id' => null,
    ]);

    $patient = Patient::factory()->create();
    $patientRecord = MediaAsset::factory()->create([
        'created_at' => $now->modify('-731 days'),
        'owner_patient_id' => $patient->id,
    ]);

    expect($service->eligibleForPurge($preConsultation, $now))->toBeTrue()
        ->and($service->eligibleForPurge($patientRecord, $now))->toBeTrue();
});

it('extends clinical retention through the patients last historical appointment', function () {
    $service = app(MediaAssetService::class);
    $now = new DateTimeImmutable('2026-10-29 00:00:00', new DateTimeZone('UTC'));

    $patient = Patient::factory()->create();
    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'start_at' => $now->modify('-1 year -1 day'),
        'end_at' => $now->modify('-1 year'),
    ]);

    $asset = MediaAsset::factory()->create([
        'owner_patient_id' => $patient->id,
        'created_at' => $now->modify('-3 years'),
    ]);

    expect($service->eligibleForPurge($asset, $now))->toBeFalse();
});

it('does not purge a soft-deleted asset until 30 days have passed', function () {
    $service = app(MediaAssetService::class);
    $now = new DateTimeImmutable('2026-10-29 00:00:00', new DateTimeZone('UTC'));

    $asset = MediaAsset::factory()->create([
        'created_at' => $now->modify('-5 years'),
        'deleted_at' => $now->modify('-29 days'),
    ]);

    expect($service->eligibleForPurge($asset, $now))->toBeFalse()
        ->and($service->eligibleForPurge($asset, $now->modify('+1 day')))->toBeTrue();
});

it('purges storage and the database row only when eligible', function () {
    Storage::fake('media');
    $service = app(MediaAssetService::class);

    $asset = MediaAsset::factory()->create([
        'storage_key' => 'media/expired.png',
        'created_at' => now('UTC')->subDays(91),
    ]);
    Storage::disk('media')->put($asset->storage_key, 'synthetic');

    expect($service->purge($asset))->toBeTrue();
    Storage::disk('media')->assertMissing($asset->storage_key);
    expect(MediaAsset::withTrashed()->find($asset->id))->toBeNull();
});
