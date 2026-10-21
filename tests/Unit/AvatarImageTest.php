<?php

namespace Tests\Unit;

use App\Support\AvatarImage;
use Tests\TestCase;

/**
 * Die Größe, in der ein Profilbild gespeichert wird: Das längste Stück wird auf
 * die Kantenlänge des Avatars gekürzt, das Seitenverhältnis bleibt. Reine
 * Rechnerei - deshalb ohne Bilddatei und ohne die GD-Erweiterung prüfbar (die
 * braucht nur das tatsächliche Verkleinern, siehe App\Support\AvatarImage).
 */
class AvatarImageTest extends TestCase
{
    public function test_a_wide_picture_is_cut_down_on_its_longest_edge(): void
    {
        $this->assertSame([512, 384], AvatarImage::fit(1600, 1200));
    }

    public function test_a_tall_picture_is_cut_down_on_its_longest_edge(): void
    {
        $this->assertSame([384, 512], AvatarImage::fit(1200, 1600));
    }

    public function test_a_picture_that_already_fits_keeps_its_size(): void
    {
        $this->assertSame([64, 64], AvatarImage::fit(64, 64));
        $this->assertSame([512, 512], AvatarImage::fit(512, 512));
    }

    public function test_a_small_picture_is_never_blown_up(): void
    {
        $this->assertSame([1, 1], AvatarImage::fit(1, 1));
        $this->assertSame([200, 150], AvatarImage::fit(200, 150));
    }

    /** Ein langes, dünnes Bild (etwa ein Banner) darf keine Kante verlieren. */
    public function test_a_very_long_picture_keeps_both_edges(): void
    {
        $this->assertSame([512, 1], AvatarImage::fit(4000, 1));
        $this->assertSame([1, 512], AvatarImage::fit(1, 4000));
    }

    /**
     * Der Typ, den ein gespeichertes Bild an den Browser bekommt, kommt aus der
     * Endung der Datei - ohne ihn zeigt ein iPhone das Bild nicht an (siehe
     * App\Http\Controllers\AvatarController).
     */
    public function test_the_type_of_a_stored_picture_comes_from_its_extension(): void
    {
        $this->assertSame('image/jpeg', AvatarImage::mimeTypeOf('avatars/avatar-1-abc.jpg'));
        $this->assertSame('image/jpeg', AvatarImage::mimeTypeOf('avatars/avatar-1-abc.JPEG'));
        $this->assertSame('image/png', AvatarImage::mimeTypeOf('avatars/avatar-1-abc.png'));
        $this->assertSame('image/webp', AvatarImage::mimeTypeOf('avatars/avatar-1-abc.webp'));
    }

    /** Eine unbekannte Endung überlässt die Erkennung dem Speicher. */
    public function test_an_unknown_extension_has_no_type_of_its_own(): void
    {
        $this->assertNull(AvatarImage::mimeTypeOf('avatars/avatar-1-abc.heic'));
        $this->assertNull(AvatarImage::mimeTypeOf('avatars/avatar-1-abc'));
    }
}
