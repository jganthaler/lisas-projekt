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
}
