<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Die Auslieferung des Profilbilds an das Gerät: `/avatars/{user}` schickt die
 * gespeicherte Datei mit dem Typ, den ein Handy und ein Rechner zum Anzeigen
 * brauchen, privat und nur an angemeldete Konten. Die Adresse trägt eine Version
 * des gespeicherten Pfades - so sehen alle Geräte dasselbe Bild, und ein neues
 * Bild ist eine neue Adresse (siehe User::avatarUrl()).
 */
class AvatarDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /** Die Bytes eines echten (1x1) PNG - siehe tests/Feature/ProfileTest.php. */
    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');
    }

    /** Ein Konto mit einem gespeicherten Bild auf dem privaten Disk. */
    private function accountWithPicture(string $extension = 'png'): User
    {
        Storage::fake('local');

        $path = 'avatars/avatar-1-test.'.$extension;

        Storage::disk('local')->put($path, $this->pngBytes());

        $user = User::factory()->create();

        $user->forceFill(['avatar_path' => $path])->save();

        return $user;
    }

    /**
     * Der Typ der Antwort kommt aus der Endung der gespeicherten Datei. Ohne ihn
     * - etwa bei einem leeren Kopf, den die Erkennung des Speichers hinterlässt -
     * zeigt ein iPhone das Bild nicht an, während ein Rechner es errät. Die Bytes
     * selbst reisen unverändert mit: dasselbe Bild auf jedem Gerät.
     */
    public function test_the_picture_arrives_with_the_type_that_fits_the_file(): void
    {
        $types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        foreach ($types as $extension => $type) {
            $user = $this->accountWithPicture($extension);

            $this->actingAs($user)
                ->get(route('avatars.show', $user))
                ->assertOk()
                ->assertHeader('Content-Type', $type)
                ->assertHeaderContains('Content-Disposition', 'inline')
                ->assertStreamedContent($this->pngBytes());
        }
    }

    /**
     * Das Bild gehört zu einer Person: Es liegt hinter der Anmeldung, und die
     * Antwort darf nur im Speicher des eigenen Browsers liegen ("private") -
     * eine Stunde lang, dann fragt der Browser wieder nach. Nach einem neuen
     * Bild ist die Adresse eine andere, deshalb kann kein Gerät beim alten
     * hängen bleiben.
     */
    public function test_the_picture_is_private_and_only_for_signed_in_people(): void
    {
        $user = $this->accountWithPicture();

        $this->get(route('avatars.show', $user))->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('avatars.show', $user))
            ->assertOk()
            ->assertHeaderContains('Cache-Control', 'private')
            ->assertHeaderContains('Cache-Control', 'max-age=3600');
    }

    /**
     * Ein Pfad ohne Datei - ein entferntes Bild, ein kopiertes Konto - ist ein
     * 404. Die Seite zeigt dann die Initialen statt eines kaputten Bildes; das
     * Konto bleibt dabei unberührt.
     */
    public function test_a_picture_that_is_gone_is_answered_with_404(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        // Ohne Bild am Konto ...
        $this->actingAs($user)->get(route('avatars.show', $user))->assertNotFound();

        // ... und mit einem Pfad, zu dem keine Datei mehr gehört.
        $user->forceFill(['avatar_path' => 'avatars/verloren.png'])->save();

        $this->actingAs($user)->get(route('avatars.show', $user))->assertNotFound();

        $this->assertSame('avatars/verloren.png', $user->refresh()->avatar_path);
    }
}
