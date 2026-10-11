<?php

namespace Tests\Feature;

use App\Livewire\Invitations\AcceptInvitation;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\Invitation;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Freezer, 1: User, 2: string}
     */
    private function invite(): array
    {
        $owner = User::factory()->create();
        $freezer = Freezer::factory()->withDrawers(1)->create(['owner_id' => $owner->getKey()]);

        ['token' => $token] = Invitation::issue($freezer, $owner);

        return [$freezer, $owner, $token];
    }

    public function test_an_invited_user_becomes_a_member(): void
    {
        [$freezer, $owner, $token] = $this->invite();
        $guest = User::factory()->create();

        Livewire::actingAs($guest)
            ->test(AcceptInvitation::class, ['token' => $token])
            ->call('accept')
            ->assertHasNoErrors()
            ->assertRedirect(route('freezer.closed'));

        $this->assertSame(FreezerMembership::ROLE_MEMBER, $guest->roleIn($freezer));
        $this->assertTrue($guest->isMemberOf($freezer));

        // The shared freezer becomes the active one right away.
        $this->assertSame($freezer->getKey(), FreezerContext::id());

        // The link is burned after the first use.
        $invitation = Invitation::query()->firstOrFail();

        $this->assertFalse($invitation->isUsable());
        $this->assertSame($guest->getKey(), $invitation->accepted_by_user_id);
        $this->assertNotNull($invitation->accepted_at);

        // Only the hash of the token is stored.
        $this->assertNotSame($token, $invitation->token_hash);
        $this->assertSame(Invitation::hashToken($token), $invitation->token_hash);
    }

    public function test_the_inviting_user_keeps_the_owner_role(): void
    {
        [$freezer, $owner, $token] = $this->invite();

        Livewire::actingAs($owner)
            ->test(AcceptInvitation::class, ['token' => $token])
            ->call('accept')
            ->assertRedirect(route('freezer.closed'));

        $this->assertSame(FreezerMembership::ROLE_OWNER, $owner->roleIn($freezer));
        $this->assertSame(1, $freezer->memberships()->count());
    }

    public function test_an_invitation_can_only_be_used_once(): void
    {
        [$freezer, $owner, $token] = $this->invite();
        $first = User::factory()->create();
        $second = User::factory()->create();

        Livewire::actingAs($first)
            ->test(AcceptInvitation::class, ['token' => $token])
            ->call('accept')
            ->assertRedirect(route('freezer.closed'));

        Livewire::actingAs($second)
            ->test(AcceptInvitation::class, ['token' => $token])
            ->call('accept')
            ->assertSet('error', 'Diese Einladung ist nicht mehr gültig. Bitte lasse dir einen neuen Link schicken.')
            ->assertNoRedirect();

        $this->assertFalse($second->isMemberOf($freezer));
        $this->assertSame(2, $freezer->memberships()->count());
    }

    public function test_an_expired_invitation_cannot_be_accepted(): void
    {
        [$freezer, $owner, $token] = $this->invite();

        Carbon::setTestNow(Carbon::now()->addDays(Invitation::VALID_DAYS + 1));

        $guest = User::factory()->create();

        Livewire::actingAs($guest)
            ->test(AcceptInvitation::class, ['token' => $token])
            ->call('accept')
            ->assertNoRedirect();

        Carbon::setTestNow();

        $this->assertFalse($guest->isMemberOf($freezer));
        $this->assertNull(Invitation::query()->firstOrFail()->accepted_at);
    }

    public function test_the_plain_token_is_never_stored(): void
    {
        [, , $token] = $this->invite();

        $this->assertDatabaseMissing('invitations', ['token_hash' => $token]);
        $this->assertDatabaseHas('invitations', ['token_hash' => Invitation::hashToken($token)]);
    }

    public function test_a_guest_is_sent_to_the_login_screen_first(): void
    {
        [, , $token] = $this->invite();

        $this->get(route('invitations.accept', ['token' => $token]))
            ->assertRedirect(route('login'));
    }

    public function test_an_expired_invitation_page_points_to_a_new_link(): void
    {
        [, , $token] = $this->invite();

        Carbon::setTestNow(Carbon::now()->addDays(Invitation::VALID_DAYS + 1));

        $this->actingAs(User::factory()->create())
            ->get(route('invitations.accept', ['token' => $token]))
            ->assertOk()
            ->assertSee('abgelaufen')
            ->assertDontSee('Einladung annehmen');

        Carbon::setTestNow();
    }

    public function test_a_member_sees_that_they_already_have_access(): void
    {
        [$freezer, $owner, $token] = $this->invite();
        $member = User::factory()->create();

        $freezer->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->get(route('invitations.accept', ['token' => $token]))
            ->assertOk()
            ->assertSee('Du bist hier schon Mitglied');
    }
}
