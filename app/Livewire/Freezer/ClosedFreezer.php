<?php

namespace App\Livewire\Freezer;

use App\Livewire\Concerns\FollowsOfflineChanges;
use App\Models\Freezer;
use App\Support\FreezerContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The freezer page: the place where the freezers themselves are managed. The
 * field at its top carries the active freezer - the tile with the icon of the
 * app, its name and who owns it - and the ways to leave it: the arrows step to
 * its neighbours, its name opens the list of all freezers, and behind the "⋮"
 * another one is created, the active one is renamed and it is deleted.
 *
 * Below the field it shows what is waiting inside the active freezer: every
 * drawer with the names of its items, alphabetically one below the other,
 * without their amount (that one stands in the drawer itself) - and it is the
 * door to the inside.
 */
#[Layout('layouts.app')]
#[Title('freezer.title')]
class ClosedFreezer extends Component
{
    use FollowsOfflineChanges;

    public string $newFreezerName = '';

    /** Whether the inline form for another freezer is open. */
    public bool $addingFreezer = false;

    public ?int $editingFreezerId = null;

    public string $editingFreezerName = '';

    /**
     * Open the inline form for another freezer. The field carries one form at a
     * time, so opening this one closes the form that renames.
     */
    public function startAddingFreezer(): void
    {
        $this->cancelRenaming();
        $this->addingFreezer = true;
    }

    public function cancelAddingFreezer(): void
    {
        $this->reset('newFreezerName', 'addingFreezer');
        $this->resetErrorBag();
    }

    /**
     * Create an additional freezer and switch to it right away.
     */
    public function addFreezer(): void
    {
        Gate::authorize('create', Freezer::class);

        $name = trim($this->newFreezerName);

        if ($name === '') {
            $this->addError('newFreezerName', __('freezer.name_required'));

            return;
        }

        $freezer = Freezer::createFor(auth()->user(), mb_substr($name, 0, Freezer::NAME_MAX_LENGTH));

        FreezerContext::select($freezer);

        $this->cancelAddingFreezer();

        session()->flash('status', __('freezer.created', ['name' => $freezer->name]));

        // The new freezer is the active one and its name is shown in the
        // header, so the page is loaded again.
        $this->redirectRoute('freezer.closed', navigate: true);
    }

    /**
     * Make another freezer the active one.
     */
    public function selectFreezer(int $freezerId): void
    {
        $freezer = $this->findFreezer($freezerId);

        FreezerContext::select($freezer);

        $this->redirectRoute('freezer.closed', navigate: true);
    }

    /**
     * Make the freezer before the active one the active one: the arrow on the
     * left of the field.
     */
    public function selectPreviousFreezer(): void
    {
        $this->stepToNeighbour(-1);
    }

    /**
     * Make the freezer after the active one the active one: the arrow on the
     * right of the field.
     */
    public function selectNextFreezer(): void
    {
        $this->stepToNeighbour(1);
    }

    /**
     * Walk the list of the freezers by one step. The first freezer follows the
     * last one, so the arrows never stop at an end and every freezer of the user
     * is reachable without opening the list. With a single freezer there is
     * nothing to step to - the field shows no arrow then - so nothing happens.
     */
    private function stepToNeighbour(int $step): void
    {
        $freezers = $this->freezers();

        if ($freezers->count() < 2) {
            return;
        }

        $active = FreezerContext::current(auth()->user());

        $index = $freezers->search(fn (Freezer $freezer): bool => $freezer->is($active));

        if ($index === false) {
            return;
        }

        FreezerContext::select($freezers->get(($index + $step + $freezers->count()) % $freezers->count()));

        $this->redirectRoute('freezer.closed', navigate: true);
    }

    /**
     * Open the inline form that renames the given freezer - the one the menu of
     * the field belongs to, so the active one. The form for another freezer
     * closes with it: the field carries one form at a time.
     */
    public function startRenaming(int $freezerId): void
    {
        $freezer = $this->findFreezer($freezerId);

        Gate::authorize('update', $freezer);

        $this->cancelAddingFreezer();
        $this->editingFreezerId = $freezer->getKey();
        $this->editingFreezerName = $freezer->name;
    }

    public function renameFreezer(): void
    {
        $freezer = $this->findFreezer((int) $this->editingFreezerId);

        Gate::authorize('update', $freezer);

        $name = trim($this->editingFreezerName);

        if ($name === '') {
            $this->addError('editingFreezerName', __('freezer.name_required'));

            return;
        }

        $freezer->update(['name' => mb_substr($name, 0, Freezer::NAME_MAX_LENGTH)]);

        $this->cancelRenaming();

        session()->flash('status', __('freezer.renamed'));

        // The name is also shown in the header, so the page is loaded again.
        $this->redirectRoute('freezer.closed', navigate: true);
    }

    public function cancelRenaming(): void
    {
        $this->reset('editingFreezerId', 'editingFreezerName');
        $this->resetErrorBag();
    }

    /**
     * Delete one of the owned freezers with all of its content. Afterwards the
     * app falls back to another freezer of the user - or provisions a fresh one,
     * so the freezer page never ends up without anything to show.
     */
    public function deleteFreezer(int $freezerId): void
    {
        $freezer = $this->findFreezer($freezerId);

        Gate::authorize('delete', $freezer);

        $name = $freezer->name;

        $freezer->delete();

        // The deleted freezer may have been the active one.
        if (FreezerContext::id() === $freezerId) {
            FreezerContext::forget();
        }

        // Another freezer of the user takes over - or a fresh one is
        // provisioned, so the freezer page never ends up without anything to
        // show. (The action redirects, so the render pass does not do this.)
        FreezerContext::current(auth()->user());

        session()->flash('status', __('freezer.deleted', ['name' => $name]));

        $this->redirectRoute('freezer.closed', navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $freezer = FreezerContext::current($user);

        return view('livewire.freezer.closed-freezer', [
            'freezer' => $freezer,
            'freezers' => $this->freezers(),
            // The drawers come with their content: the start page names what lies
            // in each of them, and the relation hands it over alphabetically
            // sorted (see Drawer::items()) - one query for all drawers.
            'drawers' => $freezer->drawers()->with('items')->get(),
            'summary' => $freezer->drawerSummary(),
        ]);
    }

    /**
     * Every freezer the user has access to, in the order the field shows them:
     * by name, so the arrows step through the same order as the list.
     *
     * @return Collection<int, Freezer>
     */
    private function freezers(): Collection
    {
        return auth()->user()->freezers()->orderBy('name')->get();
    }

    /**
     * One of the freezers the user may access - anything else is a 404.
     */
    private function findFreezer(int $freezerId): Freezer
    {
        return auth()->user()->freezers()->whereKey($freezerId)->firstOrFail();
    }
}
