<?php

namespace App\Livewire\Sharing;

use App\Models\Freezer;
use App\Models\Invitation;
use App\Support\FreezerContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Sharing a freezer: who is a member, how to invite more people and how to
 * switch between the freezers the user has access to.
 */
#[Layout('layouts.app')]
#[Title('sharing.title')]
class SharingPage extends Component
{
    public string $freezerName = '';

    public ?string $inviteLink = null;

    public ?string $feedback = null;

    public function mount(): void
    {
        $this->freezerName = $this->freezer()->name;
    }

    public function renameFreezer(): void
    {
        $freezer = $this->freezer();

        Gate::authorize('update', $freezer);

        $validated = $this->validate([
            'freezerName' => ['required', 'string', 'max:'.Freezer::NAME_MAX_LENGTH],
        ]);

        $freezer->update(['name' => $validated['freezerName']]);

        // The name is also shown in the header, so the page is loaded again.
        session()->flash('status', __('freezer.renamed'));

        $this->redirectRoute('sharing.index', navigate: true);
    }

    /**
     * Make another freezer the active one. The page stays where it is - the
     * sharing of the chosen freezer is what the user wants to see next - and is
     * built again for it: members and invitations belong to the new freezer
     * right away, and a link of the freezer before is gone.
     */
    public function selectFreezer(int $freezerId): void
    {
        $freezer = auth()->user()->freezers()->whereKey($freezerId)->firstOrFail();

        FreezerContext::select($freezer);

        $this->redirectRoute('sharing.index', navigate: true);
    }

    public function createInvitation(): void
    {
        $freezer = $this->freezer();

        Gate::authorize('invite', $freezer);

        ['token' => $token] = Invitation::issue($freezer, auth()->user());

        $this->inviteLink = route('invitations.accept', ['token' => $token]);
        $this->feedback = __('sharing.link_created', ['days' => Invitation::VALID_DAYS]);
    }

    public function revokeInvitation(int $invitationId): void
    {
        $freezer = $this->freezer();

        Gate::authorize('invite', $freezer);

        $freezer->invitations()->whereKey($invitationId)->delete();

        if ($this->inviteLink !== null) {
            $this->inviteLink = null;
        }

        $this->feedback = __('sharing.invitation_revoked');
    }

    public function removeMember(int $userId): void
    {
        $freezer = $this->freezer();

        Gate::authorize('manageMembers', $freezer);

        if ($freezer->owner_id === $userId) {
            $this->addError('members', __('sharing.owner_removed'));

            return;
        }

        $freezer->memberships()->where('user_id', $userId)->delete();

        $this->feedback = __('sharing.member_removed');
    }

    public function leaveFreezer(): void
    {
        $freezer = $this->freezer();
        $user = auth()->user();

        if ($freezer->isOwnedBy($user)) {
            $this->addError('leave', __('sharing.owner_leave'));

            return;
        }

        $freezer->memberships()->where('user_id', $user->getKey())->delete();

        FreezerContext::forget();

        session()->flash('status', __('sharing.left', ['name' => $freezer->name]));

        $this->redirectRoute('freezer.closed', navigate: true);
    }

    public function deleteFreezer(): void
    {
        $freezer = $this->freezer();

        Gate::authorize('delete', $freezer);

        $name = $freezer->name;

        $freezer->delete();

        FreezerContext::forget();

        session()->flash('status', __('freezer.deleted', ['name' => $name]));

        $this->redirectRoute('freezer.closed', navigate: true);
    }

    public function render()
    {
        $freezer = $this->freezer();
        $user = auth()->user();

        $memberships = $freezer->memberships()
            ->with('user')
            ->get()
            ->sortBy(fn ($membership): array => [
                $membership->isOwner() ? 0 : 1,
                $membership->user?->displayName() ?? '',
            ])
            ->values();

        return view('livewire.sharing.sharing-page', [
            'freezer' => $freezer,
            'memberships' => $memberships,
            'isOwner' => $freezer->isOwnedBy($user),
            'freezers' => $user->freezers()->orderBy('name')->get(),
            'openInvitations' => $freezer->invitations()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get(),
        ]);
    }

    private function freezer(): Freezer
    {
        return FreezerContext::current(auth()->user());
    }
}
