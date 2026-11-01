<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MediaAssetKind;
use App\Jobs\ProcessSmartSmile;
use App\MediaAsset;
use App\SmartSmileSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SmartSmileService
{
    public function enabled(): bool
    {
        return (bool) config('smart_smile.enabled', false)
            && (bool) config('smart_smile.release_gate', false);
    }

    /** @return array{session: SmartSmileSession, token: string} */
    public function grantConsent(): array
    {
        $token = Str::random(64);

        $session = SmartSmileSession::query()->create([
            'consent_token_hash' => hash('sha256', $token),
            'consent_version' => (string) config('smart_smile.consent_version', 'v1'),
            'consented_at' => now('UTC'),
            'status' => 'consented',
        ]);

        return ['session' => $session, 'token' => $token];
    }

    public function resolve(string $token): ?SmartSmileSession
    {
        return SmartSmileSession::query()
            ->where('consent_token_hash', hash('sha256', $token))
            ->first();
    }

    public function upload(SmartSmileSession $session, UploadedFile $file): SmartSmileSession
    {
        if ($session->revoked_at !== null) {
            throw ValidationException::withMessages(['consent' => 'Consent is no longer active.']);
        }

        if ($session->media_asset_id !== null) {
            throw ValidationException::withMessages(['media' => 'Only one image may be processed per consent.']);
        }

        $asset = app(MediaAssetService::class)->store(
            $file,
            MediaAssetKind::SmileInput,
            null,
            null,
        );

        $session->forceFill([
            'media_asset_id' => $asset->id,
            'status' => 'queued',
            'result' => null,
        ])->save();

        ProcessSmartSmile::dispatch($session->id);

        return $session->fresh(['mediaAsset']);
    }

    public function delete(SmartSmileSession $session): void
    {
        if ($session->media_asset_id !== null) {
            $asset = MediaAsset::withTrashed()->find($session->media_asset_id);

            if ($asset !== null && $asset->deleted_at === null) {
                app(MediaAssetService::class)->softDelete($asset);
            }
        }

        $session->forceFill([
            'revoked_at' => now('UTC'),
            'status' => 'deleted',
            'result' => null,
        ])->save();
    }

    public function publicState(SmartSmileSession $session): array
    {
        return [
            'status' => $session->status,
            'result' => $session->status === 'completed' ? $session->result : null,
            'non_diagnostic' => true,
            'can_delete' => $session->revoked_at === null,
        ];
    }
}
