<?php

namespace Tests\Feature;

use App\Livewire\Freezer\OpenFreezer;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The opened freezer: the drawers of the active one are managed here - adding,
 * renaming and removing.
 */
class OpenFreezerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Freezer $freezer;

    private Drawer $drawer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->freezer = Freezer::factory()->withDrawers(2)->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Gefrierschrank Küche',
        ]);

        $this->drawer = $this->freezer->drawers()->firstOrFail();
    }

    /**
     * @return Testable<OpenFreezer>
     */
    private function page(?User $user = null)
    {
        return Livewire::actingAs($user ?? $this->owner)->test(OpenFreezer::class);
    }

    public function test_the_drawers_of_the_active_freezer_are_listed(): void
    {
        $page = $this->page();

        $this->assertSame(
            ['Schublade 1', 'Schublade 2'],
            $page->viewData('drawers')->pluck('name')->all()
        );

        $page->assertSee('Neue Schublade')
            ->assertSee('Schublade 1')
            ->assertSee('Schublade 2')
            ->assertSeeHtml('wire:click="startRenaming('.$this->drawer->getKey().')"');
    }

    /**
     * Umbenennen und Löschen einer Schublade stehen hinter dem Menü ihrer Zeile
     * ("⋮", siehe resources/views/components/menu.blade.php): die Zeile behält
     * den Namen und die Zahl der Artikel, und beide Aktionen bleiben die
     * Funktionen dieser Seite. Jede Zeile hat ihr eigenes Menü - die beiden
     * Aktionen nennen die Schublade, auf die sie sich beziehen.
     */
    public function test_every_drawer_row_carries_its_own_menu(): void
    {
        $secondDrawer = $this->freezer->drawers()->skip(1)->firstOrFail();

        $this->page()
            // Das Menü sitzt in der Zeile und nennt sie in seinem Namen ...
            ->assertSeeHtml('aria-haspopup="menu"')
            ->assertSeeHtml('aria-label="Weitere Aktionen für „Schublade 1“"')
            ->assertSeeHtml('aria-label="Weitere Aktionen für „Schublade 2“"')
            ->assertSee('Umbenennen')
            ->assertSee('Löschen')
            // ... und die Aktionen gehören zu genau dieser Schublade.
            ->assertSeeHtml('wire:click="startRenaming('.$this->drawer->getKey().')"')
            ->assertSeeHtml('wire:click="startRenaming('.$secondDrawer->getKey().')"')
            ->assertSeeHtml("name: 'drawer-delete-".$this->drawer->getKey()."'")
            ->assertSeeHtml("name: 'drawer-delete-".$secondDrawer->getKey()."'")
            // Die beiden Knöpfe von früher stehen nicht mehr offen in der Zeile.
            ->assertDontSeeHtml('aria-label="Schublade umbenennen"')
            ->assertDontSeeHtml('aria-label="Schublade löschen"');
    }

    /**
     * Beide Aktionen des Menüs rufen die vorhandenen Funktionen dieser Seite:
     * "Umbenennen" bringt das Formular der Schublade hoch, "Löschen" öffnet nur
     * die Rückfrage der App - erst deren Antwort löscht die Schublade (siehe
     * components/confirm-dialog.blade.php).
     */
    public function test_the_menu_calls_the_rename_form_and_the_question_of_the_app(): void
    {
        $this->page()
            ->assertSee('Schublade löschen?')
            ->assertSee('„Schublade 1“ wird entfernt. Das geht nur, solange sie leer ist.')
            ->assertSeeHtml('wire:click="deleteDrawer('.$this->drawer->getKey().')"')
            ->call('startRenaming', $this->drawer->getKey())
            ->assertSet('editingDrawerId', $this->drawer->getKey())
            ->assertSeeHtml('wire:model="editingDrawerName"')
            ->assertSee('Name der Schublade');

        // Die Schublade steht noch: die Rückfrage allein löscht nichts.
        $this->assertDatabaseHas('drawers', ['id' => $this->drawer->getKey()]);
    }

    /**
     * The count next to a name says how much lies inside that drawer. A drawer
     * without items has no row in the summary at all - the view reads that as
     * "0 Artikel".
     */
    public function test_every_drawer_names_how_many_items_lie_in_it(): void
    {
        InventoryItem::factory()->count(2)->create(['drawer_id' => $this->drawer->getKey()]);

        $summary = $this->page()->viewData('summary');
        $secondDrawer = $this->freezer->drawers()->skip(1)->firstOrFail();

        $this->assertSame(2, $summary[$this->drawer->getKey()]['items']);
        $this->assertArrayNotHasKey($secondDrawer->getKey(), $summary);
    }

    public function test_a_drawer_can_be_added(): void
    {
        $this->page()
            ->set('newDrawerName', '  Gemüse  ')
            ->call('addDrawer')
            ->assertHasNoErrors()
            ->assertSet('newDrawerName', '')
            ->assertSet('feedback', 'Schublade hinzugefügt.');

        $drawer = $this->freezer->drawers()->where('name', 'Gemüse')->firstOrFail();

        // A new drawer lands behind the ones that are already there.
        $this->assertSame(3, $drawer->position);
        $this->assertSame(3, $this->freezer->drawers()->count());
    }

    /**
     * A drawer without a name would be a row nobody can tell apart from the
     * others, so the form asks for one instead of creating it.
     */
    public function test_a_drawer_without_a_name_is_not_created(): void
    {
        $this->page()
            ->set('newDrawerName', '   ')
            ->call('addDrawer')
            ->assertHasErrors(['newDrawerName' => 'Bitte gib der Schublade einen Namen.'])
            ->assertSee('Bitte gib der Schublade einen Namen.');

        $this->assertSame(2, $this->freezer->drawers()->count());
    }

    public function test_a_drawer_can_be_renamed(): void
    {
        $this->page()
            ->call('startRenaming', $this->drawer->getKey())
            ->assertSet('editingDrawerId', $this->drawer->getKey())
            ->assertSet('editingDrawerName', 'Schublade 1')
            ->set('editingDrawerName', 'Fleisch')
            ->call('renameDrawer')
            ->assertHasNoErrors()
            ->assertSet('editingDrawerId', null)
            ->assertSet('feedback', 'Schublade umbenannt.');

        $this->assertSame('Fleisch', $this->drawer->refresh()->name);
    }

    public function test_renaming_can_be_cancelled(): void
    {
        $this->page()
            ->call('startRenaming', $this->drawer->getKey())
            ->call('cancelRenaming')
            ->assertSet('editingDrawerId', null)
            ->assertSet('editingDrawerName', '');

        $this->assertSame('Schublade 1', $this->drawer->refresh()->name);
    }

    /**
     * Das Umbenennen-Formular endet wie jedes Formular der App: „Abbrechen“
     * links, „Speichern“ rechts, beide gleich groß in zwei gleich breiten
     * Spalten.
     */
    public function test_the_rename_form_puts_cancel_left_and_save_right_in_two_equal_columns(): void
    {
        $page = $this->page()->call('startRenaming', $this->drawer->getKey());

        $page->assertSeeHtmlInOrder([
            'wire:click="cancelRenaming" class="btn btn-ghost w-full"',
            'type="submit" class="btn btn-primary w-full"',
        ]);

        $this->assertSame(
            1,
            substr_count($page->html(), 'grid grid-cols-2 gap-2'),
            'Die beiden Knöpfe des Umbenennen-Formulars stehen nicht in zwei gleich breiten Spalten.'
        );
    }

    public function test_an_empty_drawer_can_be_deleted(): void
    {
        $this->page()
            ->call('deleteDrawer', $this->drawer->getKey())
            ->assertHasNoErrors()
            ->assertSet('feedback', 'Schublade gelöscht.');

        $this->assertDatabaseMissing('drawers', ['id' => $this->drawer->getKey()]);
    }

    /**
     * Deleting a drawer would take its items with it, so an empty one is
     * required. The message names the drawer: it stands at the top of the page,
     * far away from the row that was tapped.
     */
    public function test_a_drawer_with_items_is_not_deleted(): void
    {
        InventoryItem::factory()->count(2)->create(['drawer_id' => $this->drawer->getKey()]);

        $this->page()
            ->call('deleteDrawer', $this->drawer->getKey())
            ->assertHasErrors('drawerDeletion')
            ->assertSee('In „Schublade 1“ liegen noch Artikel. Bitte zuerst alle entfernen.');

        $this->assertDatabaseHas('drawers', ['id' => $this->drawer->getKey()]);
        $this->assertSame(2, $this->drawer->items()->count());
    }

    /**
     * The page works on the active freezer only: a drawer of another freezer of
     * the household is unknown here.
     */
    public function test_only_the_drawers_of_the_active_freezer_can_be_touched(): void
    {
        $otherFreezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $this->owner->getKey()]);
        $otherDrawer = $otherFreezer->drawers()->firstOrFail();

        $this->page()->call('deleteDrawer', $otherDrawer->getKey())->assertNotFound();
        $this->page()->call('startRenaming', $otherDrawer->getKey())->assertNotFound();

        $this->assertDatabaseHas('drawers', ['id' => $otherDrawer->getKey()]);
    }

    public function test_the_drawers_of_a_foreign_freezer_stay_out_of_reach(): void
    {
        // A freezer of somebody else: neither the stranger (who owns a freezer
        // of their own) nor the member of this household may touch its drawers.
        $otherOwner = User::factory()->create();
        $foreignFreezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $otherOwner->getKey()]);
        $foreignDrawer = $foreignFreezer->drawers()->firstOrFail();

        $stranger = User::factory()->create();

        $this->page($stranger)->call('deleteDrawer', $foreignDrawer->getKey())->assertNotFound();
        $this->page($this->owner)->call('deleteDrawer', $foreignDrawer->getKey())->assertNotFound();

        $this->assertDatabaseHas('drawers', ['id' => $foreignDrawer->getKey()]);
    }

    /**
     * A member of a shared freezer works in it like its owner does: the drawers
     * belong to the household, not to one person.
     */
    public function test_a_member_can_manage_the_drawers_of_a_shared_freezer(): void
    {
        $member = User::factory()->create();

        $this->freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        FreezerContext::select($this->freezer);

        $this->page($member)
            ->set('newDrawerName', 'Beeren')
            ->call('addDrawer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('drawers', [
            'freezer_id' => $this->freezer->getKey(),
            'name' => 'Beeren',
        ]);
    }
}
