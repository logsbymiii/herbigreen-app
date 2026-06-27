<x-filament-panels::page>
    <form wire:submit="kirim">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Kirim Broadcast
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
