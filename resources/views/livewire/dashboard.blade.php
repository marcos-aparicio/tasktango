<?php

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new
    #[Layout('layouts.app')]
    class extends Component
    {
        #[Reactive]
        public Collection $tasks;

        #[On('task-created')]
        public function updateTaskList()
        {
            $this->tasks = auth()
                ->user()
                ->tasks()
                ->orderByDesc('id')
                ->get();
        }

        public function mount(): void
        {
            $this->tasks = auth()
                ->user()
                ->tasks()
                ->orderByDesc('id')
                ->get();
        }
    }
?>
<div class="flex flex-col gap-4">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-secondary leading-tight">
            Inbox
        </h2>
    </x-slot>
</div>
