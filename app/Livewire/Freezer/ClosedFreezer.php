<?php

namespace App\Livewire\Freezer;

use App\Livewire\Concerns\FollowsOfflineChanges;
use App\Models\Freezer;
use App\Support\FreezerContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The freezer as the user knows it from home: closed. It shows what is waiting
 * inside - every drawer with its content, name and amount, alphabetically one
 * below the other - and how many items need attention, and it is the door to
 * the inside.
 *
 * It is also the place where the freezers themselves are managed: every freezer
 * the user has access to is listed, another one can be created and the owned
 * ones can be renamed.
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
     * Open the inline form for another freezer.
     */
    public function startAddingFreezer(): void
    {
        $this->resetErrorBag();
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

    public function startRenaming(int $freezerId): void
    {
        $freezer = $this->findFreezer($freezerId);

        Gate::authorize('update', $freezer);

        $this->editingFreezerId = $freezer->getKey();
        $this->editingFreezerName = $freezer->name;
        $this->resetErrorBag();
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
            'freezers' => $user->freezers()->orderBy('name')->get(),
            // The drawers come with their content: the start page names what lies
            // in each of them, and the relation hands it over alphabetically
            // sorted (see Drawer::items()) - one query for all drawers.
            'drawers' => $freezer->drawers()->with('items')->get(),
            'summary' => $freezer->drawerSummary(),
            'itemCount' => $freezer->itemCount(),
        ]);
    }

    /**
     * One of the freezers the user may access - anything else is a 404.
     */
    private function findFreezer(int $freezerId): Freezer
    {
        return auth()->user()->freezers()->whereKey($freezerId)->firstOrFail();
    }
}
