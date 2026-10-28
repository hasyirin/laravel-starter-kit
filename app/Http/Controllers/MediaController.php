<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __invoke(Request $request, Media $media, ?string $conversion = null): StreamedResponse
    {
        abort_if($media->model === null, 404);

        Gate::authorize('view', $media->model);

        abort_unless($conversion === null || $media->hasGeneratedConversion($conversion), 404);

        $response = match (true) {
            // Media Library sends a conversion with the type of the original file, so the disk reads the type of the conversion.
            $conversion !== null => Storage::disk($media->conversions_disk)->response($media->getPathRelativeToRoot($conversion)),
            str_starts_with($media->mime_type, 'image/') && $media->mime_type !== 'image/svg+xml' => $media->toInlineResponse($request),
            default => $media->toResponse($request),
        };

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
