<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the uploaded profile pictures. The files live on the private "local"
 * disk, therefore they are streamed through the app instead of being exposed
 * publicly.
 */
class AvatarController extends Controller
{
    public function __invoke(User $user): StreamedResponse
    {
        abort_unless($user->hasAvatar(), 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists((string) $user->avatar_path), 404);

        return $disk->response((string) $user->avatar_path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
