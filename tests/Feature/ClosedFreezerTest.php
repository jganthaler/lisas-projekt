<?php

namespace Tests\Feature;

use App\Livewire\Freezer\ClosedFreezer;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Managing the freezers themselves on the "Gefrierschrank" page: creating
 * another freezer, switching between them and renaming the owned ones.
 */
class ClosedFreezerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Freezer $freezer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->freezer = Freezer::factory()->withDrawers()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Gefrierschrank Küche',
        ]);
    }

    /**
     * @return Testable<ClosedFreezer>
     */
    private function page(User $user)
    {
        return Livewire::actingAs($user)->test(ClosedFreezer::class);
    }

    /**
     * Somebody who may work in the freezer without owning it.
     */
    private function member(): User
    {
        $member = User::factory()->create();

        $this->freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        return $member;
    }

    public function test_the_page_lists_every_freezer_of_the_user(): void
    {
        Freezer::factory()->sharedWith($this->owner)->create(['name' => 'Truhe im Keller']);

        $page = $this->page($this->owner);

        $this->assertSame(
            ['Gefrierschrank Küche', 'Truhe im Keller'],
            $page->viewData('freezers')->pluck('name')->all()
        );

        // The owned freezer is the active one, as long as nothing was chosen.
        $this->assertTrue($page->viewData('freezer')->is($this->freezer));

        $page->assertSee('Meine Gefrierschränke')
            ->assertSee('Gefrierschrank Küche')
            ->assertSee('Truhe im Keller')
            // The form for another freezer only appears on request.
            ->assertDontSee('Neuer Gefrierschrank');
    }

    /**
     * The start page no longer nags about items whose MHD is due: the warning pill
     * of the app frame and the MHD page carry that information.
     */
    public function test_the_start_page_does_not_warn_about_the_mhd(): void
    {
        InventoryItem::factory()->expired()->create([
            'drawer_id' => $this->freezer->drawers()->firstOrFail()->getKey(),
        ]);

        $this->page($this->owner)
            ->assertDontSee('Artikel prüfen')
            ->assertDontSee('Alles frisch')
            ->assertDontSee('abgelaufen');
    }

    /**
     * Die Schublade auf der Startseite ist ein Blick hinein: sie nennt ihren
     * Inhalt alphabetisch untereinander, aber nur Name und Menge. Gezählt wird
     * dort nicht mehr - die Zahl steht oben in der Zusammenfassung des
     * Gefrierschranks.
     */
    public function test_a_drawer_names_its_items_with_their_quantity(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Zucchini', 'quantity' => 2]);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Äpfel', 'quantity' => 3]);
        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen', 'quantity' => 5]);

        // „Äpfel“ steht vor „Erbsen“: sortiert wird über den Sortierschlüssel
        // („aepfel“), nicht über das Zeichen selbst. Hinter jedem Namen steht
        // seine Menge.
        $html = $this->page($this->owner)
            ->assertSeeInOrder(['Schublade 1', 'Äpfel', '3', 'Erbsen', '5', 'Zucchini', '2'])
            ->html();

        // Gezählt werden die Artikel nur noch oben am Gefrierschrank
        // („3 Artikel in 3 Schubladen“), nicht mehr in jeder Schublade.
        $this->assertSame(1, substr_count($html, '3 Artikel'));
    }

    /**
     * Eine leere Schublade sagt das selbst - statt einer Liste steht dort der
     * Hinweis, den auch die Schublade selbst kennt.
     */
    public function test_an_empty_drawer_says_that_it_is_empty(): void
    {
        $this->page($this->owner)->assertSee('Diese Schublade ist noch leer.');
    }

    /**
     * Gelesen wird auf der Startseite nur: die Zeile einer Schublade ist ein
     * Link in die Schublade. Geändert wird ein Artikel dort, wo er liegt - hier
     * stellt also niemand eine Menge ein.
     */
    public function test_the_items_cannot_be_changed_on_the_start_page(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey(), 'name' => 'Erbsen', 'quantity' => 2]);

        $this->page($this->owner)
            ->assertSeeHtml('href="'.route('drawers.show', $drawer).'"')
            ->assertDontSeeHtml('decrementQuantity')
            ->assertDontSeeHtml('incrementQuantity')
            ->assertDontSeeHtml('startEditingItem')
            ->assertDontSeeHtml('startMovingItem');
    }

    /**
     * Managing the drawers is the last thing on the start page - and it is called
     * "Schubladen bearbeiten" now.
     */
    public function test_the_drawer_button_sits_at_the_bottom_of_the_page(): void
    {
        $html = $this->page($this->owner)->html();

        $this->assertStringContainsString('Schubladen bearbeiten', $html);
        $this->assertStringNotContainsString('Gefrierschrank öffnen', $html);

        // Nothing but the drawers themselves comes before the button.
        $this->assertGreaterThan(
            strrpos($html, 'drawer-row'),
            strpos($html, 'Schubladen bearbeiten'),
        );
    }

    public function test_the_form_for_a_new_freezer_appears_on_the_plus_button(): void
    {
        $this->page($this->owner)
            ->assertSeeHtml('aria-label="Gefrierschrank hinzufügen"')
            ->call('startAddingFreezer')
            ->assertSet('addingFreezer', true)
            ->assertSee('Neuer Gefrierschrank');
    }

    public function test_adding_a_freezer_can_be_cancelled(): void
    {
        $this->page($this->owner)
            ->call('startAddingFreezer')
            ->set('newFreezerName', 'Truhe im Keller')
            ->call('cancelAddingFreezer')
            ->assertSet('addingFreezer', false)
            ->assertSet('newFreezerName', '')
            ->assertDontSee('Neuer Gefrierschrank');

        $this->assertSame(1, $this->owner->freezers()->count());
    }

    public function test_a_second_freezer_can_be_created_with_its_own_drawers(): void
    {
        $this->page($this->owner)
            ->call('startAddingFreezer')
            ->set('newFreezerName', 'Truhe im Keller')
            ->call('addFreezer')
            ->assertSet('addingFreezer', false)
            ->assertSet('newFreezerName', '')
            ->assertRedirect(route('freezer.closed'))
            ->assertSessionHas('status');

        $created = Freezer::query()->where('name', 'Truhe im Keller')->firstOrFail();

        $this->assertSame($this->owner->getKey(), $created->owner_id);
        $this->assertTrue($this->owner->isMemberOf($created));
        $this->assertSame(
            ['Schublade 1', 'Schublade 2', 'Schublade 3'],
            $created->drawers()->pluck('name')->all()
        );

        // The new freezer is empty and becomes the active one right away.
        $this->assertSame(0, $created->itemCount());
        $this->assertSame($created->getKey(), FreezerContext::id());
        $this->assertSame(2, $this->owner->freezers()->count());
    }

    public function test_a_member_can_create_a_freezer_of_their_own(): void
    {
        $member = $this->member();

        $this->page($member)
            ->set('newFreezerName', 'Eigene Truhe')
            ->call('addFreezer')
            ->assertRedirect(route('freezer.closed'));

        $own = $member->ownedFreezers()->where('name', 'Eigene Truhe')->firstOrFail();

        $this->assertTrue($member->isMemberOf($own));
        $this->assertSame($own->getKey(), FreezerContext::id());
    }

    public function test_creating_a_freezer_needs_a_name(): void
    {
        $this->page($this->owner)
            ->set('newFreezerName', '   ')
            ->call('addFreezer')
            ->assertHasErrors('newFreezerName');

        $this->assertSame(1, $this->owner->freezers()->count());
    }

    public function test_a_freezer_name_is_shortened_to_the_allowed_length(): void
    {
        $this->page($this->owner)
            ->set('newFreezerName', Str::repeat('a', Freezer::NAME_MAX_LENGTH + 20))
            ->call('addFreezer');

        $created = $this->owner->ownedFreezers()->latest('id')->firstOrFail();

        $this->assertSame(Freezer::NAME_MAX_LENGTH, mb_strlen($created->name));
    }

    public function test_the_owner_can_rename_a_freezer(): void
    {
        $this->page($this->owner)
            ->call('startRenaming', $this->freezer->getKey())
            ->assertSet('editingFreezerId', $this->freezer->getKey())
            ->assertSet('editingFreezerName', 'Gefrierschrank Küche')
            ->set('editingFreezerName', 'Kühltruhe')
            ->call('renameFreezer')
            ->assertRedirect(route('freezer.closed'))
            ->assertSessionHas('status');

        $this->assertSame('Kühltruhe', $this->freezer->refresh()->name);

        // Renaming does not touch the selection.
        $this->assertSame($this->freezer->getKey(), FreezerContext::id());
    }

    public function test_renaming_needs_a_name(): void
    {
        $this->page($this->owner)
            ->call('startRenaming', $this->freezer->getKey())
            ->set('editingFreezerName', ' ')
            ->call('renameFreezer')
            ->assertHasErrors('editingFreezerName');

        $this->assertSame('Gefrierschrank Küche', $this->freezer->refresh()->name);
    }

    public function test_renaming_can_be_cancelled(): void
    {
        $this->page($this->owner)
            ->call('startRenaming', $this->freezer->getKey())
            ->call('cancelRenaming')
            ->assertSet('editingFreezerId', null)
            ->assertSet('editingFreezerName', '');

        $this->assertSame('Gefrierschrank Küche', $this->freezer->refresh()->name);
    }

    public function test_a_member_may_not_rename_a_shared_freezer(): void
    {
        $member = $this->member();

        $this->page($member)
            ->call('startRenaming', $this->freezer->getKey())
            ->assertForbidden();

        $this->assertSame('Gefrierschrank Küche', $this->freezer->refresh()->name);
    }

    public function test_a_foreign_freezer_cannot_be_renamed(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('startRenaming', $foreign->getKey())
            ->assertNotFound();

        $this->assertNotSame('Gefrierschrank Küche', $foreign->refresh()->name);
    }

    public function test_the_active_freezer_can_be_switched_on_the_freezer_page(): void
    {
        $shared = Freezer::factory()->sharedWith($this->owner)->create(['name' => 'Truhe im Keller']);

        $this->page($this->owner)
            ->call('selectFreezer', $shared->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertSame($shared->getKey(), FreezerContext::id());
        $this->assertTrue(FreezerContext::current($this->owner)->is($shared));
    }

    public function test_a_foreign_freezer_cannot_be_selected(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('selectFreezer', $foreign->getKey())
            ->assertNotFound();

        $this->assertSame($this->freezer->getKey(), FreezerContext::id());
    }

    public function test_the_delete_button_asks_through_the_dialog_first(): void
    {
        $this->page($this->owner)
            ->assertSeeHtml("name: 'freezer-delete-".$this->freezer->getKey()."'")
            ->assertSee('Gefrierschrank löschen?')
            ->assertSee('„Gefrierschrank Küche“ verschwindet mit allen Schubladen und Artikeln für immer.')
            ->assertSeeHtml('wire:click="deleteFreezer('.$this->freezer->getKey().')"');
    }

    public function test_the_owner_can_delete_a_freezer_with_its_content(): void
    {
        $drawer = $this->freezer->drawers()->firstOrFail();

        InventoryItem::factory()->create(['drawer_id' => $drawer->getKey()]);

        $this->page($this->owner)
            ->call('deleteFreezer', $this->freezer->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertDatabaseMissing('freezers', ['id' => $this->freezer->getKey()]);
        $this->assertDatabaseMissing('drawers', ['freezer_id' => $this->freezer->getKey()]);
        $this->assertDatabaseCount('inventory_items', 0);

        // The page never stands there without a freezer, so a fresh one takes over.
        $fresh = $this->owner->freezers()->firstOrFail();

        $this->assertNotSame($this->freezer->getKey(), $fresh->getKey());
        $this->assertSame(Freezer::DEFAULT_DRAWER_COUNT, $fresh->drawers()->count());
        $this->assertTrue(FreezerContext::current($this->owner)->is($fresh));
    }

    public function test_deleting_the_active_freezer_switches_to_another_one(): void
    {
        $other = Freezer::factory()->withDrawers()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Truhe im Keller',
        ]);

        FreezerContext::select($this->freezer);

        $this->page($this->owner)
            ->call('deleteFreezer', $this->freezer->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertDatabaseMissing('freezers', ['id' => $this->freezer->getKey()]);
        $this->assertTrue(FreezerContext::current($this->owner)->is($other));
    }

    public function test_a_member_may_not_delete_a_shared_freezer(): void
    {
        $member = $this->member();

        $this->page($member)
            ->call('deleteFreezer', $this->freezer->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('freezers', ['id' => $this->freezer->getKey()]);
    }

    public function test_a_foreign_freezer_cannot_be_deleted(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('deleteFreezer', $foreign->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('freezers', ['id' => $foreign->getKey()]);
    }
}
