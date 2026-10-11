<div class="space-y-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('freezer.open') }}" wire:navigate class="btn btn-ghost btn-icon"
           aria-label="Zurück zum Gefrierschrank">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>

        <div class="min-w-0 flex-1">
            <h2 class="truncate text-base font-bold text-frost-900">{{ $drawer->name }}</h2>
            <p class="truncate text-xs text-frost-500">
                {{ $freezer->name }} · {{ $items->count() }} Artikel
            </p>
        </div>
    </div>

    <x-notice :message="$itemFeedback" />

    {{-- New item --}}
    <form wire:submit="addItem" class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">Artikel hinzufügen</h3>

        <div>
            <label class="label" for="name">Was kommt rein?</label>
            <input id="name" type="text" wire:model="name" placeholder="z. B. Erbsen"
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <div class="w-28">
                <label class="label" for="quantity">Menge</label>
                <input id="quantity" type="number" min="1" max="999" inputmode="numeric" wire:model="quantity"
                       class="input @error('quantity') input-error @enderror">
                @error('quantity')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex-1">
                <label class="label" for="bestBefore">MHD (optional)</label>
                <input id="bestBefore" type="date" wire:model="bestBefore"
                       class="input @error('bestBefore') input-error @enderror">
                @error('bestBefore')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="addItem">
            <x-icon name="plus" class="h-5 w-5" />
            Hinzufügen
        </button>
    </form>

    <section class="space-y-2">
        <h3 class="px-1 text-sm font-semibold text-frost-700">Inhalt</h3>

        @forelse ($items as $item)
            @if ($editingItemId === $item->id)
                <form wire:submit="saveItem" class="card space-y-3 p-4" wire:key="edit-{{ $item->id }}">
                    <div>
                        <label class="label" for="editName">Artikel</label>
                        <input id="editName" type="text" wire:model="editName"
                               class="input @error('editName') input-error @enderror">
                        @error('editName')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <div class="w-28">
                            <label class="label" for="editQuantity">Menge</label>
                            <input id="editQuantity" type="number" min="1" max="999" inputmode="numeric"
                                   wire:model="editQuantity" class="input @error('editQuantity') input-error @enderror">
                            @error('editQuantity')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex-1">
                            <label class="label" for="editBestBefore">MHD</label>
                            <input id="editBestBefore" type="date" wire:model="editBestBefore"
                                   class="input @error('editBestBefore') input-error @enderror">
                            @error('editBestBefore')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <x-icon name="check" class="h-5 w-5" />
                            Speichern
                        </button>
                        <button type="button" wire:click="cancelEditingItem" class="btn btn-ghost">Abbrechen</button>
                    </div>
                </form>
            @else
                <x-item-row :item="$item" :editable="true" :confirming="$confirmingItemId === $item->id" />
            @endif
        @empty
            <div class="card px-4 py-10 text-center">
                <x-icon name="snowflake" class="mx-auto h-8 w-8 text-frost-300" />
                <p class="mt-2 text-sm font-medium text-frost-700">Diese Schublade ist noch leer.</p>
            </div>
        @endforelse
    </section>
</div>
