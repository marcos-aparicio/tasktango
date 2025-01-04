<?php

use App\Enums\TaskFrequencies;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Volt\Component;

new class extends Component
{
    #[Modelable]
    public $frequency;

    #[Locked]
    public ?bool $bigger = false;

    public ?array $frequencies;

    public function mount(): void
    {
        $this->frequencies = array_map(function ($p) {
            return ['id' => $p->value, 'name' => ucfirst(strtolower($p->name))];
        }, TaskFrequencies::cases());
    }
};

?>
<x-choices label="Frequency (Optional)" single wire:model="frequency" :options="$frequencies" @class([ "select-sm text-sm" => !$bigger ])/>
