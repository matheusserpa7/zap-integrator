<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Media\Exceptions\MediaExpired;
use App\Models\MediaObject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaController
{
    public function show(Request $request, MediaObject $media): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('view', $media), 404);

        if ($media->isExpired()) {
            throw new MediaExpired;
        }

        $disk = Storage::disk((string) config('zap.media.disk', 'media'));

        if (! $disk->exists($media->disk_path)) {
            throw new MediaExpired;
        }

        return $disk->download(
            $media->disk_path,
            $media->filename,
            [
                'Content-Type' => $media->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
