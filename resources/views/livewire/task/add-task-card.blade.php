<?php

use App\Enums\TaskStatuses;
use App\Livewire\Forms\CreateTaskForm;
use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    #[Reactive]
    public $prefilledData = [];

    public CreateTaskForm $form;

    /**
     * @var the attributes that the form shows, meaning the ones that its errors will be shown
     * alongside their components, this variable is created so that any other attributes that are
     * not shown in the form can be shown in a general error message
     */
    public array $attributesShown = [
        'form.name',
        'form.description',
        'form.priority',
        'form.due_date',
        'form.frequency',
        'form.assignee_user_id',
        'form.project',
        'form.selected_individual_labels',
        'form.selected_project_labels'
    ];

    // hidden/shown flags
    public ?bool $showAddForm = false;
    public bool $showLabelsModal = false;
    public ?string $label = 'New task';

    public function updatedForm($value, $field)
    {
        // clearing project labels if the user changes the project since project labels are only from that particular project
        if ($field === 'project') {
            $this->form->selected_project_labels = [];
        }
    }

    public function mount(): void
    {
        // assign default values
        $this->status = TaskStatuses::PENDING->value;

        if (!isset($this->prefilledData))
            return;

        $this->form->setData($this->prefilledData);
        if (request()->project) {
            $this->form->project = request()->project->id;
        }
    }

    public function showCard(): void
    {
        $this->showAddForm = true;
        $this->form->setData($this->prefilledData);
    }

    #[On('task-modal-closed')]
    public function resetData()
    {
        if (!isset($this->form->parent_task_id))
            return;
        $this->form->reset();
        $this->showAddForm = false;
    }

    // functions
    public function createTask()
    {
        $this->validate();

        $this->form->createTask();

        $this->success(title: 'Task created', position: 'toast-bottom toast-end text-wrap');
        $this->dispatch('task-created');
        $this->form->reset();
        $this->showAddForm = false;
    }
};

?>

<div x-cloak dusk='add-task-card' class="bg-base shadow-sm sm:rounded-lg my-2 w-full flex" >
    <x-button :$label class="btn-ghost ml-auto" icon="o-plus" @click="$wire.showCard()"
        x-show="!$wire.showAddForm" />
    <x-form wire:submit="createTask" class="bg-base-300 px-4 py-4 rounded-xl flex flex-col gap-4 ml-auto w-fit"
        x-show="$wire.showAddForm">
        @php
        $theError = '';
        $additionalErrors = [];
        foreach ($errors->getMessages() as $key => $value) {
            if(in_array($key, $attributesShown)){
                    continue;
            }
            $theError = $value[0];
            break;
        }
        @endphp
        {{--<div class="grid grid-cols-6 gap-4">--}}
        <div class="flex flex-col gap-2">
        @if ($theError !== '')
            <div role="alert" class="alert alert-error col-span-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{$theError}}</span>
            </div>
        @endif
            <div class="flex flex-col gap-4 col-span-full md:col-span-4">
                <x-input type="text" placeholder="Enter task" dusk="task-name"
                    class="font-bold  focus:outline-none text-xl border-none" wire:model="form.name" />

                <x-textarea rows="3" placeholder="Description" dusk="task-description"
                    class="border-none focus:outline-none focus:border-none focus:ring-transparent"
                    wire:model="form.description" inline />
            </div>
            {{--<div class="flex gap-3 flex-col col-span-6 md:col-span-2 justify-between">--}}
            <div class="flex gap-3 flex-wrap max-md:justify-around">
                <livewire:task.subcomps.priority wire:model="form.priority" />
                <x-datetime label="Due (Optional)" wire:model="form.due_date" icon="o-calendar" class="input-sm pl-8" dusk="add-task-due-date"/>
                <livewire:task.subcomps.frequency wire:model="form.frequency" />
            @if(isset($form->project) && App\Models\Project::find($form->project)->users->count() > 1)
                <livewire:task.subcomps.assignee wire:model="form.assignee_user_id" :project="\App\Models\Project::find($form->project)" />
            @endif
            @isset($form->project)
                <livewire:task.subcomps.subproject wire:model="form.subproject_id" :project="\App\Models\Project::find($form->project)" />
            @endisset
            </div>
        </div>


        <x-slot:actions>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div class="flex gap-2">
                    <livewire:task.subcomps.projects wire:model.live="form.project" class="select-xs"/>
                    <x-button label="Labels" class="btn btn-primary !btn-sm my-auto" type="button" icon="o-tag" responsive
                        @click="$wire.showLabelsModal = true" />
                </div>

                <div class="flex items-center gap-2">
                    <x-button label="Cancel" class="btn btn-secondary btn-sm" icon="o-x-mark" responsive
                        @click="$wire.showAddForm = false" />
                    <x-button label="Add task" class="btn btn-primary btn-sm" type="submit" icon="o-paper-airplane"
                        responsive />
                </div>
            </div>
        </x-slot:actions>

    </x-form>
    <x-modal wire:model="showLabelsModal" title="Add Labels" boxClass="max-w-5xl !overflow-y-visible" dusk="labels-modal">
        <livewire:task.subcomps.user-labels wire:model="form.selected_individual_labels" />
        @isset($form->project)
        @php
            $project = App\Models\Project::find($form->project);
        @endphp
            <livewire:task.subcomps.project-labels wire:model.live="form.selected_project_labels" :project="$project" :key="$project->id" />
        @endisset
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showLabelsModal = false" dusk="labels-modal-cancel" />
        </x-slot:actions>
    </x-modal>
</div>
