<x-filament-panels::page>
    <x-filament-panels::form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" size="lg">
                Generate Number
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
