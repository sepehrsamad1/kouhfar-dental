<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MediaAssetKind;
use App\Http\Requests\MediaAssetUploadRequest;
use App\Jobs\PurgeMediaAsset;
use App\MediaAsset;
use App\Services\MediaAssetService;
use App\StaffUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

final class MediaAssetController
{
    public function store(MediaAssetUploadRequest $request, MediaAssetService $service): JsonResponse
    {
        Gate::authorize('create', MediaAsset::class);

        /** @var StaffUser $actor */
        $actor = $request->user('staff');

        /** @var UploadedFile $upload */
        $upload = $request->file('media');

        $asset = $service->store(
            $upload,
            MediaAssetKind::from($request->string('kind')->toString()),
            $request->input('owner_patient_id'),
            $actor->id,
        );

        return response()->json([
            'id' => $asset->id,
            'mime_type' => $asset->mime_type,
            'byte_size' => $asset->byte_size,
            'url' => URL::temporarySignedRoute(
                'staff.media.download',
                now('UTC')->addMinutes(15),
                ['mediaAsset' => $asset->id],
            ),
        ], 201);
    }

    public function download(MediaAsset $mediaAsset): Response
    {
        Gate::authorize('view', $mediaAsset);

        if ($mediaAsset->deleted_at !== null) {
            abort(404);
        }

        $disk = Storage::disk('media');
        if (! $disk->exists($mediaAsset->storage_key)) {
            abort(404);
        }

        return response()->streamDownload(
            function () use ($disk, $mediaAsset): void {
                $stream = $disk->readStream($mediaAsset->storage_key);
                if ($stream === false) {
                    abort(404);
                }

                fpassthru($stream);
                fclose($stream);
            },
            'media-'.$mediaAsset->id.'.'.pathinfo($mediaAsset->storage_key, PATHINFO_EXTENSION),
            [
                'Content-Type' => $mediaAsset->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function delete(MediaAsset $mediaAsset, MediaAssetService $service): RedirectResponse
    {
        Gate::authorize('delete', $mediaAsset);

        $service->softDelete($mediaAsset);
        PurgeMediaAsset::dispatch($mediaAsset->id)->delay(now('UTC')->addDays(30));

        return back()->with('success', 'رسانه حذف شد و پس از دوره نگهداری پاک‌سازی می‌شود.');
    }
}
