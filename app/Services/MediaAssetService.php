<?php

declare(strict_types=1);

namespace App\Services;

use App\Appointment;
use App\Enums\MediaAssetKind;
use App\MediaAsset;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MediaAssetService
{
    public const MAX_BYTES = 10_485_760;

    private const MAX_PIXELS = 40_000_000;

    private const MAX_DIMENSION = 10_000;

    private const FORMATS = [
        'image/jpeg' => ['extension' => 'jpg'],
        'image/png' => ['extension' => 'png'],
        'image/webp' => ['extension' => 'webp'],
    ];

    public function store(UploadedFile $upload, MediaAssetKind $kind, ?string $ownerPatientId, ?string $createdBy): MediaAsset
    {
        if (! extension_loaded('gd')) {
            throw ValidationException::withMessages(['media' => 'Image processing is unavailable.']);
        }

        if ($upload->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['media' => 'The image exceeds the 10 MB limit.']);
        }

        $bytes = @file_get_contents($upload->getRealPath() ?: '');
        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages(['media' => 'The uploaded image could not be read.']);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($bytes);
        if (! is_string($mime) || ! isset(self::FORMATS[$mime])) {
            throw ValidationException::withMessages(['media' => 'Only JPEG, PNG and WebP images are accepted.']);
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size[0] < 1 || $size[1] < 1 || $size['mime'] !== $mime) {
            throw ValidationException::withMessages(['media' => 'The uploaded file is not a valid image.']);
        }

        $width = (int) $size[0];
        $height = (int) $size[1];
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION || $width * $height > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['media' => 'The image dimensions are not supported.']);
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw ValidationException::withMessages(['media' => 'The uploaded image could not be decoded.']);
        }

        $extension = self::FORMATS[$mime]['extension'];
        $temporary = tempnam(sys_get_temp_dir(), 'kouhfar-media-');
        if ($temporary === false) {
            imagedestroy($image);
            throw ValidationException::withMessages(['media' => 'The image could not be processed.']);
        }

        try {
            $encoded = match ($mime) {
                'image/jpeg' => @imagejpeg($image, $temporary, 88),
                'image/png' => $this->encodePng($image, $temporary),
                'image/webp' => function_exists('imagewebp') && @imagewebp($image, $temporary, 88),
            };

            if ($encoded !== true || ! is_file($temporary)) {
                throw ValidationException::withMessages(['media' => 'The image format is not supported by the server image processor.']);
            }

            $processed = file_get_contents($temporary);
            if ($processed === false || $processed === '') {
                throw ValidationException::withMessages(['media' => 'The processed image could not be read.']);
            }

            if ($finfo->buffer($processed) !== $mime) {
                throw ValidationException::withMessages(['media' => 'Image processing produced an unexpected file type.']);
            }

            $storageKey = 'media/'.(string) Str::uuid().'.'.$extension;
            if (! Storage::disk('media')->put($storageKey, $processed)) {
                throw ValidationException::withMessages(['media' => 'The image could not be stored.']);
            }

            try {
                return MediaAsset::query()->create([
                    'storage_key' => $storageKey,
                    'mime_type' => $mime,
                    'byte_size' => strlen($processed),
                    'sha256' => hash('sha256', $processed),
                    'owner_patient_id' => $ownerPatientId,
                    'kind' => $kind,
                    'created_by' => $createdBy,
                    'created_at' => now('UTC'),
                ]);
            } catch (\Throwable $e) {
                Storage::disk('media')->delete($storageKey);
                throw $e;
            }
        } finally {
            imagedestroy($image);
            @unlink($temporary);
        }
    }

    public function softDelete(MediaAsset $mediaAsset): void
    {
        $mediaAsset->delete();
    }

    public function purge(MediaAsset $mediaAsset): bool
    {
        if (! $this->eligibleForPurge($mediaAsset)) {
            return false;
        }

        Storage::disk('media')->delete($mediaAsset->storage_key);
        $mediaAsset->forceDelete();

        return true;
    }

    public function eligibleForPurge(MediaAsset $mediaAsset, ?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($mediaAsset->deleted_at !== null) {
            $deletedAt = new DateTimeImmutable((string) $mediaAsset->deleted_at, new DateTimeZone('UTC'));

            return $deletedAt->getTimestamp() <= $now->modify('-30 days')->getTimestamp();
        }

        if ($mediaAsset->created_at === null) {
            return false;
        }

        $createdAt = new DateTimeImmutable((string) $mediaAsset->created_at, new DateTimeZone('UTC'));

        if ($mediaAsset->owner_patient_id === null) {
            return $createdAt->getTimestamp() <= $now->modify('-90 days')->getTimestamp();
        }

        $anchor = $createdAt;
        $lastAppointmentEnd = Appointment::query()
            ->where('patient_id', $mediaAsset->owner_patient_id)
            ->where('end_at', '<=', $now->format('Y-m-d H:i:sP'))
            ->max('end_at');

        if (is_string($lastAppointmentEnd)) {
            $appointmentDate = new DateTimeImmutable($lastAppointmentEnd, new DateTimeZone('UTC'));
            if ($appointmentDate->getTimestamp() > $anchor->getTimestamp()) {
                $anchor = $appointmentDate;
            }
        }

        return $anchor->getTimestamp() <= $now->modify('-2 years')->getTimestamp();
    }

    private function encodePng(\GdImage $image, string $path): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return @imagepng($image, $path, 6);
    }
}
