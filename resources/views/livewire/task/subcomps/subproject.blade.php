<?php

use App\Models\Project;
use App\Models\SubProject;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    #[Modelable]
    public ?int $subproject_id = null;

    public Project $project;

    #[Locked]
    public bool $doNotCreate = false;

    public bool $openSubprojectCreate = false;

    public string $newSubprojectName = '';

    public Collection $subprojects;

    public function mount(): void
    {
        $this->fillSubprojects();
    }

    #[On('refresh-subprojects')]
    public function fillSubprojects(): void
    {
        if ($this->project->subprojects->isEmpty()) {
            $this->subprojects = collect([['id' => null, 'name' => 'None']]);
            return;
        }
        $this->subprojects = $this->project->subprojects->map(function ($subproject) {
            return ['id' => $subproject->id, 'name' => $subproject->name];
        });
        $this->subprojects->prepend(['id' => null, 'name' => 'None']);
    }

    public function createSubproject(): void
    {
        $validator = Validator::make(
            ['newSubproject' => $this->newSubprojectName],
            [
                'newSubproject' => [
                    'required',
                    'string',
                    'min:3',
                    'max:10',
                    function ($attribute, $value, $fail) {
                        if ($this
                                ->project
                                ->subprojects()
                                ->whereNotNull('name')
                                ->where('name', $value)
                                ->exists()) {
                            $fail('The subproject name must be unique within the project.');
                        }
                    },
                ],
            ],
            [
                'newSubproject.required' => 'Subproject name is required.',
                'newSubproject.string' => 'Subproject name must be a string.',
                'newSubproject.min' => 'Subproject name must be at least 3 characters.',
                'newSubproject.max' => 'Subproject name must be at most 10 characters.',
            ]
        );
        if ($validator->fails()) {
            $this->addError('newSubprojectName', $validator->errors()->first());
            return;
        }

        $newSubproject = new SubProject();
        $newSubproject->name = $this->newSubprojectName;
        $newSubproject->project()->associate($this->project);
        $newSubproject->creator()->associate(auth()->user());
        $newSubproject->save();
        $this->subprojects->push(['id' => $newSubproject->id, 'name' => $newSubproject->name]);
        $this->subproject_id = $newSubproject->id;
        $this->newSubprojectName = '';
        $this->openSubprojectCreate = false;
        $this->dispatch('refresh-subprojects');
    }
};

?>
<div>
@if($doNotCreate)
<x-select label="Subproject" icon="o-hashtag" :options="$subprojects" wire:model="subproject_id">
</x-select>
@else
    <div x-show="!$wire.openSubprojectCreate">
        <x-select label="Subproject" icon="o-hashtag" :options="$subprojects" wire:model="subproject_id" class="select-sm text-sm">
            <x-slot:append>
                <x-button label="Create" icon="o-plus" class="rounded-s-none btn-primary btn-sm" @click="$wire.openSubprojectCreate = true;" />
            </x-slot:append>
        </x-select>
    </div>
    <div x-show="$wire.openSubprojectCreate" class="flex flex-col gap-2">
        <x-input label="Subproject" placeholder="Your new subproject name" icon="o-hashtag" wire:model="newSubprojectName" class="input-sm"/>
        <div>
            <x-button label="Cancel" class="btn-xs" @click="$wire.openSubprojectCreate = false;" icon="o-x-mark" responsive/>
            <x-button label="Create Subproject!" class="btn-xs btn-primary" wire:click="createSubproject" spinner="save4" />
            <div class="divider p-0.5 m-0"></div>
        </div>
    </div>
@endif
</div>
