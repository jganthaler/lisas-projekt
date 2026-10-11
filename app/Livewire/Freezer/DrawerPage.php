<?php

namespace App\Livewire\Freezer;

use App\Livewire\Concerns\InteractsWithItems;
use App\Models\Drawer;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Everything inside a single drawer: adding, editing, refilling and removing
 * items. The drawer itself is bound through the route, so the id cannot be
 * manipulated from the browser.
 */
#[Layout('layouts.app')]
#[Title('Schublade')]
class DrawerPage extends Component
{
    use InteractsWithItems;

    #[Locked]
    public int $drawerId;

    public string $name = '';

    public string $quantity = '1';

    public string $bestBefore = '';

    public ?int $editingItemId = null;

    public string $editName = '';

    public string $editQuantity = '1';

    public string $editBestBefore = '';

    public function mount(Drawer $drawer): void
    {
        Gate::authorize('view', $drawer);

        $this->drawerId = $drawer->getKey();
    }

    public function addItem(): void
    {
        $drawer = $this->drawer();

        Gate::authorize('update', $drawer);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'bestBefore' => ['nullable', 'date'],
        ]);

        $drawer->items()->create([
            'name' => $validated['name'],
            'quantity' => (int) $validated['quantity'],
            'best_before' => $validated['bestBefore'] ?: null,
        ]);

        $this->reset('name', 'bestBefore');
        $this->quantity = '1';
        $this->resetErrorBag();
        $this->itemFeedback = 'Artikel hinzugefügt.';
    }

    public function startEditingItem(int $itemId): void
    {
        $item = $this->findItem($itemId);

        Gate::authorize('update', $item);

        $this->editingItemId = $item->getKey();
        $this->editName = $item->name;
        $this->editQuantity = (string) $item->quantity;
        $this->editBestBefore = $item->best_before?->format('Y-m-d') ?? '';
        $this->cancelDelete();
        $this->resetErrorBag();
    }

    public function saveItem(): void
    {
        $item = $this->findItem((int) $this->editingItemId);

        Gate::authorize('update', $item);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:80'],
            'editQuantity' => ['required', 'integer', 'min:1', 'max:999'],
            'editBestBefore' => ['nullable', 'date'],
        ]);

        $item->update([
            'name' => $validated['editName'],
            'quantity' => (int) $validated['editQuantity'],
            'best_before' => $validated['editBestBefore'] ?: null,
        ]);

        $this->cancelEditingItem();
        $this->itemFeedback = 'Artikel aktualisiert.';
    }

    public function cancelEditingItem(): void
    {
        $this->reset('editingItemId', 'editName', 'editQuantity', 'editBestBefore');
        $this->resetErrorBag();
    }

    public function render()
    {
        $drawer = $this->drawer();

        return view('livewire.freezer.drawer-page', [
            'drawer' => $drawer,
            'freezer' => $drawer->freezer,
            'items' => $drawer->items()->get(),
        ]);
    }

    private function drawer(): Drawer
    {
        return Drawer::query()->findOrFail($this->drawerId);
    }

    private function findItem(int $itemId): InventoryItem
    {
        return $this->drawer()->items()->whereKey($itemId)->firstOrFail();
    }
}
