<?php

namespace Tests\Feature;

use App\Livewire\Sharing\SharingPage;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\Invitation;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class SharingPageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Freezer $freezer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->freezer = Freezer::factory()->withDrawers(1)->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Kühltruhe',
        ]);
    }

    /**
     * @return Testable<SharingPage>
     */
    private function page(User $user)
    {
        return Livewire::actingAs($user)->test(SharingPage::class);
    }

    private function member(): User
    {
        $member = User::factory()->create();

        $this->freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        return $member;
    }

    public function test_the_page_shows_the_members_of_the_active_freezer(): void
    {
        $member = $this->member();

        $page = $this->page($this->owner);

        $this->assertSame($this->freezer->getKey(), $page->viewData('freezer')->getKey());
        $this->assertTrue($page->viewData('isOwner'));
        $this->assertCount(2, $page->viewData('memberships'));

        // The owner is listed first.
        $this->assertSame(
            FreezerMembership::ROLE_OWNER,
            $page->viewData('memberships')->first()->role
        );

        $page->assertSee($member->displayName());
    }

    public function test_the_owner_can_create_an_invitation_link(): void
    {
        $page = $this->page($this->owner)->call('createInvitation');

        $link = $page->get('inviteLink');

        $this->assertIsString($link);
        $this->assertStringContainsString('/invitations/', $link);
        $this->assertDatabaseCount('invitations', 1);

        // The plain token only exists in the link, the database keeps the hash.
        $token = Str::afterLast($link, '/');

        $this->assertDatabaseHas('invitations', [
            'freezer_id' => $this->freezer->getKey(),
            'inviter_id' => $this->owner->getKey(),
            'token_hash' => Invitation::hashToken($token),
        ]);
    }

    public function test_only_the_owner_may_invite(): void
    {
        $member = $this->member();

        $this->page($member)->call('createInvitation')->assertForbidden();

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_an_invitation_can_be_revoked(): void
    {
        ['invitation' => $invitation] = Invitation::issue($this->freezer, $this->owner);

        $this->page($this->owner)
            ->call('revokeInvitation', $invitation->getKey())
            ->assertSet('feedback', 'Einladung zurückgezogen.');

        $this->assertDatabaseMissing('invitations', ['id' => $invitation->getKey()]);
    }

    public function test_the_owner_can_rename_the_freezer(): void
    {
        $this->page($this->owner)
            ->set('freezerName', 'Kühltruhe im Keller')
            ->call('renameFreezer')
            ->assertHasNoErrors()
            ->assertRedirect(route('sharing.index'));

        $this->assertSame('Kühltruhe im Keller', $this->freezer->refresh()->name);
    }

    public function test_a_member_cannot_rename_the_freezer(): void
    {
        $member = $this->member();

        $this->page($member)
            ->set('freezerName', 'Umgetauft')
            ->call('renameFreezer')
            ->assertForbidden();

        $this->assertSame('Kühltruhe', $this->freezer->refresh()->name);
    }

    public function test_the_owner_can_remove_a_member_but_not_themselves(): void
    {
        $member = $this->member();
        $page = $this->page($this->owner);

        $page->call('removeMember', $member->getKey())
            ->assertSet('feedback', 'Mitglied entfernt.');

        $this->assertFalse($member->isMemberOf($this->freezer));

        // The owner membership cannot be removed - the freezer needs an owner.
        $page->call('removeMember', $this->owner->getKey())
            ->assertHasErrors('members');

        $this->assertTrue($this->owner->isMemberOf($this->freezer));
    }

    public function test_a_member_can_leave_a_shared_freezer(): void
    {
        $member = $this->member();

        $this->page($member)
            ->call('leaveFreezer')
            ->assertRedirect(route('freezer.closed'));

        $this->assertFalse($member->isMemberOf($this->freezer));
        $this->assertNull(FreezerContext::id());
    }

    public function test_the_owner_cannot_leave_their_own_freezer(): void
    {
        $this->page($this->owner)
            ->call('leaveFreezer')
            ->assertHasErrors('leave');

        $this->assertTrue($this->owner->isMemberOf($this->freezer));
    }

    public function test_the_owner_can_delete_the_freezer_with_all_its_content(): void
    {
        $drawer = Drawer::factory()->create(['freezer_id' => $this->freezer->getKey()]);
        $item = InventoryItem::factory()->create(['drawer_id' => $drawer->getKey()]);

        $this->page($this->owner)
            ->call('deleteFreezer')
            ->assertRedirect(route('freezer.closed'));

        $this->assertDatabaseMissing('freezers', ['id' => $this->freezer->getKey()]);
        $this->assertDatabaseMissing('drawers', ['id' => $drawer->getKey()]);
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_a_member_cannot_delete_the_freezer(): void
    {
        $member = $this->member();

        $this->page($member)->call('deleteFreezer')->assertForbidden();

        $this->assertDatabaseHas('freezers', ['id' => $this->freezer->getKey()]);
    }

    public function test_the_active_freezer_can_be_switched(): void
    {
        $shared = Freezer::factory()->sharedWith($this->owner)->create(['name' => 'Geteilter Schrank']);

        // Without an explicit choice the owned freezer wins.
        $this->page($this->owner);

        $this->assertSame($this->freezer->getKey(), FreezerContext::id());

        $this->page($this->owner)
            ->call('selectFreezer', $shared->getKey())
            ->assertRedirect(route('freezer.closed'));

        $this->assertSame($shared->getKey(), FreezerContext::id());
    }

    public function test_a_foreign_freezer_cannot_be_selected(): void
    {
        $foreign = Freezer::factory()->create();

        $this->page($this->owner)
            ->call('selectFreezer', $foreign->getKey())
            ->assertNotFound();

        $this->assertSame($this->freezer->getKey(), FreezerContext::id());
    }
}
