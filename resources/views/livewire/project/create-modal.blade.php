<?php

use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $name;

    public string $description;

    public bool $openTaskModal = false;

    #[On('new-project-modal')]
    public function showModal(): void
    {
        $this->openTaskModal = true;
    }

    public function createProject(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|min:3',
            'description' => 'nullable|string|max:512',
        ]);

        $res = auth()->user()->createProject($this->name, $this->description ?? '');

        if (!$res['success']) {
            $this->error(title: 'Error creating project. Please try again later', message: $res['message']);
            return;
        }

        $this->reset();
        $this->openTaskModal = false;
        $this->dispatch('reload-projects');
        $this->success(
            title: 'Project created successfully',
            position: 'toast-bottom toast-end text-wrap',
            description: 'Start organizing!',
            redirectTo: route('project.show', $res['project']->id)
        );
    }
};

?>

<x-modal boxClass="max-w-3xl flex flex-col pt-0 px-0" wire:model="openTaskModal">
    {{-- modal's header --}}
    <div class="flex justify-between p-4 pb-2">
        <p>Create a new awesome project!</p>
        <x-button icon="o-x-mark" class="btn-ghost btn-circle btn-xs" @click="$wire.openTaskModal = false;"responsive/>
    </div>
    <div class="divider divider-primary my-0 h-fit"></div>
    {{-- modal's body --}}
    <x-form class="flex flex-col gap-4 p-4" wire:submit="createProject">
        <x-input label="What will be the name of your project?" placeholder="State your goal clearly and shortly (i.e) Finish capstone before its to late..."
            wire:model="name"></x-input>
        <x-textarea rows="5"
            label="You can put a description too, it's optional though" placeholder="Dude you have to finish this, always remember what the Prof said, 'Premature optimization is the root of all evil'..."
            wire:model="description" />
        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark"  @click="$wire.openTaskModal = false;" responsive></x-button>
            <x-button label="Create Project!" icon="o-plus" class="btn-primary" type="submit" responsive></x-button>

        </x-slot:actions>

    </x-form>
</x-modal>
