<?php

namespace Tests\Feature;

use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: every screen of the app has to render without a Blade error.
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_redirects_to_the_freezer(): void
    {
        $this->get('/')->assertRedirect('/freezer');
    }

    /**
     * The top row of the app frame carries the word mark of the app instead of the
     * name of the active freezer.
     */
    public function test_the_app_frame_shows_the_word_mark_in_the_top_row(): void
    {
        $owner = User::factory()->create();

        Freezer::factory()->withDrawers()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'Gefrierschrank Küche',
        ]);

        $html = $this->actingAs($owner)->get(route('freezer.closed'))->assertOk()->getContent();

        $start = strpos($html, '<header');
        $header = substr($html, $start, strpos($html, '</header>') - $start);

        $this->assertStringContainsString('aria-label="Coolventory"', $header);
        $this->assertStringNotContainsString('Gefrierschrank Küche', $header);
    }

    public function test_every_page_renders_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $freezer = Freezer::factory()->withDrawers(2)->create(['owner_id' => $owner->getKey()]);
        $drawer = $freezer->drawers()->first();

        InventoryItem::factory()->expired()->create(['drawer_id' => $drawer->getKey()]);
        InventoryItem::factory()->expiringSoon()->create(['drawer_id' => $drawer->getKey()]);
        InventoryItem::factory()->fresh()->create(['drawer_id' => $drawer->getKey()]);

        $this->actingAs($owner);

        foreach ([
            route('freezer.closed'),
            route('freezer.open'),
            route('drawers.show', $drawer),
            route('inventory.index'),
            route('expiring.index'),
            route('sharing.index'),
            route('profile.edit'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_invitation_page_renders_for_the_invited_user(): void
    {
        $owner = User::factory()->create();
        $freezer = Freezer::factory()->create(['owner_id' => $owner->getKey()]);
        ['token' => $token] = Invitation::issue($freezer, $owner);
        $guest = User::factory()->create();

        $this->actingAs($guest)
            ->get(route('invitations.accept', ['token' => $token]))
            ->assertOk();
    }

    public function test_an_unknown_invitation_token_is_a_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('invitations.accept', ['token' => 'gibt-es-nicht']))
            ->assertNotFound();
    }
}
