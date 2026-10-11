<?php

namespace App\Livewire\Freezer;

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
#[Title('Gefrierschrank geöffnet')]
class OpenFreezer extends Component
{
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
            $this->addError('newDrawerName', 'Bitte gib der Schublade einen Namen.');

            return;
        }

        $freezer->drawers()->create([
            'name' => mb_substr($name, 0, 60),
            'position' => ((int) $freezer->drawers()->max('position')) + 1,
        ]);

        $this->reset('newDrawerName');
        $this->resetErrorBag();
        $this->feedback = 'Schublade hinzugefügt.';
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
            $this->addError('editingDrawerName', 'Bitte gib der Schublade einen Namen.');

            return;
        }

        $drawer->update(['name' => mb_substr($name, 0, 60)]);

        $this->cancelRenaming();
        $this->feedback = 'Schublade umbenannt.';
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
        if ($drawer->items()->exists()) {
            $this->addError('drawerDeletion', 'Die Schublade ist noch nicht leer. Bitte zuerst alle Artikel entfernen.');

            return;
        }

        $drawer->delete();

        $this->cancelRenaming();
        $this->feedback = 'Schublade gelöscht.';
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
