<?php

use App\Enums\TaskPriorities;
use Livewire\Attributes\Modelable;
use Livewire\Volt\Component;

new class extends Component
{
    #[Modelable]
    public $priority;

    #[Locked]
    public ?bool $bigger = false;

    public ?array $priorities;

    public function mount(): void
    {
        $this->priorities = array_map(function ($p) {
            return ['id' => $p->value, 'name' => "$p->name"];
        }, TaskPriorities::cases());
    }
};

?>
<x-choices label="Priority (Optional)" :options="$priorities" single wire:model="priority" @class(["select-sm text-sm" => !$bigger ]) dusk="priority-input">
    {{-- Item slot --}}
    @scope('item', $p)
        <x-list-item :item="$p" icon="s-flag">
            <x-slot:avatar>
                <x-icon name="s-flag" class="text-{{ config('constants.tasks.priority_colors')[$p['id']] }}" />
            </x-slot:avatar>
        </x-list-item>
    @endscope
    {{-- Selection slot --}}
    @scope('selection', $p)
        <x-icon name="s-flag" class="text-{{ config('constants.tasks.priority_colors')[$p['id']] }}" />
        {{ $p['name'] }}
    @endscope
</x-choices>
