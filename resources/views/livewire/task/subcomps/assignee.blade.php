<?php

use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Volt\Component;

new class extends Component
{
    public Project $project;

    #[Modelable]
    public ?int $selected_user = null;

    #[Locked]
    public ?bool $bigger = false;

    public Collection $users;

    public function mount(): void
    {
        $this->users = $this->project->users->map(function ($user) {
            if ($user->id == auth()->id())
                return ['id' => $user->id, 'name' => $user->username . ' (You)'];

            return ['id' => $user->id, 'name' => $user->username];
        });
        $this->users->prepend(['id' => null, 'name' => 'Unassigned']);
    }
};

?>
<x-select label="Assign to (Optional)" wire:model="selected_user" :options="$users" @class(['select-sm text-sm'=> !$bigger])/>
