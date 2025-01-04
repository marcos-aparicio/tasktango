<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    #[Locked]
    public bool $doNotCreate = false;

    #[Reactive]
    public Project $project;

    #[Modelable]
    public $selected_project_labels;

    public Collection $allProjectLabels;
    public bool $openLabelCreate = false;
    public string $newLabelName = '';

    public function getLabels(): void
    {
        $selectedLabels = $this
            ->project
            ->labels()
            ->whereIn('id', $this->selected_project_labels)
            ->get();

        $otherLabels = $this
            ->project
            ->labels()
            ->whereNotIn('id', $this->selected_project_labels)
            ->get();

        $this->allProjectLabels = $selectedLabels->merge($otherLabels);
    }

    public function mount(): void
    {
        $this->getLabels();
    }

    /**
     * Creates an Project label for the project or adds the new label to the project's labels if the label already exists.
     */
    public function createProjectLabel(): void
    {
        $this->validate([
            'newLabelName' => [
                'required',
                'string',
                'min:3',
                'max:20',
                function ($attribute, $value, $fail) {
                    if ($this->project->labels()->where('name', $value)->exists()) {
                        $fail('The label name must be unique within the project.');
                    }
                },
            ],
        ]);

        $label = auth()->user()->labels()->create(['name' => $this->newLabelName]);
        $label->project()->associate($this->project);
        $label->save();
        $this->newLabelName = '';
        $this->openLabelCreate = false;
        $this->selected_project_labels[] = $label->id;
        $this->getLabels();
        $this->success(title: "Project Label '{$this->newLabelName}' created", position: 'toast-bottom toast-end text-wrap');
    }
};

?>
<div>
@if($doNotCreate)
    <x-custom-choices class="flex-1 max-h-20 overflow-y-scroll" label="Project Labels (all project members can see them)" wire:model="selected_project_labels" icon="o-tag" :options="$allProjectLabels" dusk="project-labels" multiple compact/>
@else
    <div x-show="!$wire.openLabelCreate">
        <x-custom-choices class="flex-1 max-h-20 overflow-y-scroll" label="Project Labels (all project members can see them)" wire:model="selected_project_labels" icon="o-tag" :options="$allProjectLabels" dusk="project-labels" multiple compact>
            <x-slot:append>
                <x-button label="Create" icon="o-plus" class="rounded-s-none btn-primary" @click="$wire.openLabelCreate = true;" />
            </x-slot:append>
        </x-custom-choices>
    </div>
    <div x-show="$wire.openLabelCreate" class="flex flex-col gap-2" >
        <x-input label="New Project Label" placeholder="Your new label name" icon="o-tag" wire:model="newLabelName" class="input-sm"/>
        <div>
            <x-button label="Cancel" class="btn-xs" @click="$wire.openLabelCreate = false;" icon="o-x-mark" responsive/>
            <x-button label="Create Label!" class="btn-xs btn-primary" wire:click="createProjectLabel" spinner="save4" dusk="modal-label-create-buton-project"/>
            <div class="divider p-0.5 m-0"></div>
        </div>

    </div>
@endif
</div>
