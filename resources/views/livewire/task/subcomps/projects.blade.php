<?php

use App\Enums\TaskFrequencies;
use Illuminate\Support\Facades\Request;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    #[Modelable]
    public $project;

    #[Locked]
    public ?bool $bigger = false;

    #[Locked]
    public ?bool $dontDisable = false;

    public bool $should_disable = false;

    public ?array $projects;

    public function mount(): void
    {
        if (!$this->dontDisable) {
            $this->should_disable =
                request()->project !== null ||
                (str_contains(url()->previous(), '/project/') && str_contains(url()->current(), '/livewire/update'));
        }
        $this->loadProjects();
    }

    #[On('reload-projects')]
    public function loadProjects(): void
    {
        $projects = auth()->user()->projects()->get()->map(function ($p) {
            return ['id' => $p->id, 'name' => $p->name];
        });
        $projects->prepend(['id' => null, 'name' => 'Inbox']);  // important line dont delete
        $this->projects = $projects->toArray();
    }
};

?>
<x-select
    label="Project"
    :options="$projects"
    wire:model.live="project"
    :disabled="$should_disable"
    @class([ "!select-md" => !$bigger ])
    dusk="project" />
