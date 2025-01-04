<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    #[Locked]
    public bool $doNotCreate = false;

    #[Modelable]
    public $selected_individual_labels;

    public ?array $selectedProjectLabels;
    public bool $openLabelCreate = false;
    public string $newLabelName = '';
    public Collection $allIndividualLabels;

    #[On('getLabels')]
    public function getLabels()
    {
        $user = Auth::user();
        $selectedLabels = $user
            ->labels()
            ->whereIn('id', $this->selected_individual_labels ?? [])
            ->whereNull('project_id')
            ->get();

        $otherLabels = $user
            ->labels()
            ->whereNotIn('id', $this->selected_individual_labels ?? [])
            ->whereNull('project_id')
            ->get();

        $this->allIndividualLabels = $selectedLabels->merge($otherLabels);
    }

    public function mount(): void
    {
        $this->getLabels();
    }

    /**
     * Creates an Individual label for the user or adds the new label to the user's labels if the label already exists.
     */
    public function createIndividualLabel(): void
    {
        $this->validate([
            'newLabelName' => [
                'required',
                'string',
                'min:3',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (Auth::user()->labels()->where('name', $value)->whereNull('project_id')->exists()) {
                        $fail('The label name must be unique.');
                    }
                },
            ],
        ]);

        $label = auth()->user()->labels()->create(['name' => $this->newLabelName]);
        $this->selected_individual_labels[] = $label->id;
        $this->dispatch('getLabels');
        $this->getLabels();
        $this->success(title: "Individual Label '{$this->newLabelName}' created", position: 'toast-bottom toast-end text-wrap');
        $this->newLabelName = '';
        $this->openLabelCreate = false;
    }
};

?>
<div>
@if($doNotCreate)
    <x-custom-choices class="flex-1 max-h-20 overflow-y-scroll" label="Individual Labels (only you can see them)" wire:model="selected_individual_labels" icon="o-tag" :options="$allIndividualLabels" dusk="individual-labels" multiple compact/>
@else
    <div x-show="!$wire.openLabelCreate">
        <x-custom-choices class="flex-1 max-h-20 overflow-y-scroll" label="Individual Labels (only you can see them)" wire:model="selected_individual_labels" icon="o-tag" :options="$allIndividualLabels" dusk="individual-labels" multiple compact>
            <x-slot:append>
                <x-button label="Create" icon="o-plus" class="rounded-s-none btn-primary" @click="$wire.openLabelCreate = true;" dusk="user-label-create-button"/>
            </x-slot:append>
        </x-custom-choices>
    </div>
    <div x-show="$wire.openLabelCreate" class="flex flex-col gap-2" >
        <x-input label="New Individual Label" placeholder="Your new label name" icon="o-tag" wire:model="newLabelName" class="input-sm" dusk="search-input"/>
        <div>
            <x-button label="Cancel" class="btn-xs" @click="$wire.openLabelCreate = false;" icon="o-x-mark" responsive/>
            <x-button label="Create Label!" class="btn-xs btn-primary" wire:click="createIndividualLabel" spinner="save4" dusk="modal-label-create-buton-individual"/>
            <div class="divider p-0.5 m-0"></div>
        </div>

    </div>
@endif
</div>
