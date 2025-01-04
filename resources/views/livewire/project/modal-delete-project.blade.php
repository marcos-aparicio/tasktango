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

    #[On('open-delete-project-modal')]
    public function showModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function deleteProject()
    {
        $message = $this->authorizeOrFail('delete', $this->project);
        if ($message !== null) {
            $this->error($message);
            return;
        }
        $this->project->delete();
        $this->showModal = false;
        $this->success(title: 'Project deleted successfully', redirectTo: route('inbox'));
    }
};
?>
<x-modal title="Delete Project" wire:model="showModal">
        <div class="flex flex-col gap-2">
            <div class="text-lg">
                Are you sure you want to delete this project?
                <span class="font-bold text-primary">
                    {{$project->name}}
                </span>
            </div>
            <div class="text-error">
                This action cannot be undone. It will permanently delete the project and all its associated data, including tasks, comments, notes and files.
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="closeModal" />
            <x-button label="Confirm" class="btn-error" wire:click="deleteProject"/>
        </x-slot:actions>
</x-modal>
