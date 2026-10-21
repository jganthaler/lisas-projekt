<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AvatarImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the uploaded profile pictures. The files live on the private "local"
 * disk, therefore they are streamed through the app instead of being exposed
 * publicly.
 *
 * Der Typ kommt aus der Endung der Datei und nicht aus ihrem Inhalt: Ein Handy
 * (und jeder Browser, der einen Download anbietet statt eines Bildes) zeigt ein
 * Bild ohne passenden Typ nicht an, und die Endung kennt die App, weil sie die
 * Datei selbst geschrieben hat (App\Support\AvatarImage). Nur eine unbekannte
 * Endung überlässt die Erkennung dem Speicher.
 */
class AvatarController extends Controller
{
    public function __invoke(User $user): StreamedResponse
    {
        abort_unless($user->hasAvatar(), 404);

        $disk = Storage::disk('local');
        $path = (string) $user->avatar_path;

        abort_unless($disk->exists($path), 404);

        // "private": das Bild gehört zu einer Person und darf in keinem
        // gemeinsamen Zwischenspeicher liegen. Die Stunde gilt nur für die
        // Adresse mit ihrer Version - nach einem neuen Bild ist sie eine andere
        // (siehe User::avatarUrl()).
        $headers = ['Cache-Control' => 'private, max-age=3600'];

        if ($mimeType = AvatarImage::mimeTypeOf($path)) {
            $headers['Content-Type'] = $mimeType;
        }

        return $disk->response($path, null, $headers);
    }
}
