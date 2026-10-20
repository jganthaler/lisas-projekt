<?php

namespace App\Livewire\Freezer;

use App\Livewire\Concerns\FollowsOfflineChanges;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Support\FreezerContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The opened freezer: all drawers with their content counters plus everything
 * needed to manage the drawers themselves.
 */
#[Layout('layouts.app')]
#[Title('freezer.open_title')]
class OpenFreezer extends Component
{
    use FollowsOfflineChanges;

    public string $newDrawerName = '';

    public ?int $editingDrawerId = null;

    public string $editingDrawerName = '';

    public ?string $feedback = null;

    public function addDrawer(): void
    {
        $freezer = $this->freezer();

        Gate::authorize('create', [Drawer::class, $freezer]);

        $name = trim($this->newDrawerName);

        if ($name === '') {
            $this->addError('newDrawerName', __('freezer.drawer_name_required'));

            return;
        }

        $freezer->drawers()->create([
            'name' => mb_substr($name, 0, 60),
            'position' => ((int) $freezer->drawers()->max('position')) + 1,
        ]);

        $this->reset('newDrawerName');
        $this->resetErrorBag();
        $this->feedback = __('freezer.drawer_added');
    }

    public function startRenaming(int $drawerId): void
    {
        $drawer = $this->findDrawer($drawerId);

        Gate::authorize('update', $drawer);

        $this->editingDrawerId = $drawer->getKey();
        $this->editingDrawerName = $drawer->name;
        $this->resetErrorBag();
    }

    public function renameDrawer(): void
    {
        $drawer = $this->findDrawer((int) $this->editingDrawerId);

        Gate::authorize('update', $drawer);

        $name = trim($this->editingDrawerName);

        if ($name === '') {
            $this->addError('editingDrawerName', __('freezer.drawer_name_required'));

            return;
        }

        $drawer->update(['name' => mb_substr($name, 0, 60)]);

        $this->cancelRenaming();
        $this->feedback = __('freezer.drawer_renamed');
    }

    public function cancelRenaming(): void
    {
        $this->reset('editingDrawerId', 'editingDrawerName');
        $this->resetErrorBag();
    }

    public function deleteDrawer(int $drawerId): void
    {
        $drawer = $this->findDrawer($drawerId);

        Gate::authorize('delete', $drawer);

        // Deleting would cascade into the items, so an empty drawer is required.
        // The message names the drawer: it stands at the top of the page, and
        // "which one was it?" is exactly what a user asks there.
        if ($drawer->items()->exists()) {
            $this->addError('drawerDeletion', __('freezer.drawer_not_empty', ['name' => $drawer->name]));

            return;
        }

        $drawer->delete();

        $this->cancelRenaming();
        $this->feedback = __('freezer.drawer_deleted');
    }

    public function render()
    {
        $freezer = $this->freezer();

        return view('livewire.freezer.open-freezer', [
            'freezer' => $freezer,
            'drawers' => $freezer->drawers()->get(),
            'summary' => $freezer->drawerSummary(),
        ]);
    }

    private function freezer(): Freezer
    {
        return FreezerContext::current(auth()->user());
    }

    private function findDrawer(int $drawerId): Drawer
    {
        return $this->freezer()->drawers()->whereKey($drawerId)->firstOrFail();
    }
}
