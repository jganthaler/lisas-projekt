<?php

namespace App\Livewire\Invitations;

use App\Models\Invitation;
use App\Support\FreezerContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Landing page of an invitation link. The token is resolved once in mount();
 * the component itself only works with the (locked) invitation id afterwards.
 */
#[Layout('layouts.app')]
#[Title('invitations.title')]
class AcceptInvitation extends Component
{
    #[Locked]
    public int $invitationId;

    public ?string $error = null;

    public function mount(string $token): void
    {
        $invitation = Invitation::findByToken($token);

        abort_if($invitation === null, 404);

        $this->invitationId = $invitation->getKey();
    }

    public function accept(): void
    {
        $invitation = Invitation::query()->with('freezer')->find($this->invitationId);
        $user = auth()->user();

        if ($invitation === null || $invitation->freezer === null) {
            $this->error = __('invitations.invalid');

            return;
        }

        /*
         | Wer schon Mitglied ist, braucht die Einladung nicht: der Klick führt
         | nur in den Gefrierschrank und verbraucht den Link dabei nicht. Sonst
         | würde ein Blick auf den eigenen Link - den sich der Besitzer vor dem
         | Verschicken gerne ansieht - ihn für die eingeladene Person
         | unbrauchbar machen.
        */
        if (! $user->isMemberOf($invitation->freezer)) {
            // accept() locks the row, so the same link can never be used twice.
            if (! $invitation->accept($user)) {
                $this->error = __('invitations.invalid');

                return;
            }

            session()->flash('status', __('invitations.accepted', ['name' => $invitation->freezer->name]));
        } else {
            session()->flash('status', __('invitations.already_member_status', ['name' => $invitation->freezer->name]));
        }

        FreezerContext::select($invitation->freezer);

        $this->redirectRoute('freezer.closed', navigate: true);
    }

    public function decline(): void
    {
        $this->redirectRoute('freezer.closed', navigate: true);
    }

    public function render()
    {
        $invitation = Invitation::query()->with('freezer', 'inviter')->find($this->invitationId);
        $user = auth()->user();

        return view('livewire.invitations.accept-invitation', [
            'invitation' => $invitation,
            'alreadyMember' => $invitation !== null && $invitation->freezer !== null
                && $user->isMemberOf($invitation->freezer),
        ]);
    }
}
