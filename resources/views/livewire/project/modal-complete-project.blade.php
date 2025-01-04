<?php

use App\Models\Project;
use App\Traits\HandlesAuthorization;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use HandlesAuthorization;

    #[Locked]
    public Project $project;

    #[Locked]
    public bool $showModal = false;

    #[On('open-complete-project-modal')]
    public function showModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function completeProject()
    {
        $message = $this->authorizeOrFail('complete', $this->project);
        if ($message !== null) {
            $this->error($message);
            return;
        }
        $this->project->complete();
        $this->showModal = false;
        $this->success(title: 'Project completed successfully', redirectTo: route('project.show', $this->project));
    }
};
?>
<x-modal title="Complete Project" wire:model="showModal">
        <div class="flex flex-col gap-2">
            <div class="text-lg">
                Are you sure you want to complete this project?
                <span class="font-bold text-primary">
                    {{$project->name}}
                </span>
            </div>
            <div class="text-error">
                This action cannot be undone. It will permanently mark all tasks as completed, and the project will be read-only.
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="closeModal" />
            <x-button label="Confirm" class="btn-success" wire:click="completeProject"/>
        </x-slot:actions>
</x-modal>
