<?php

namespace App\Livewire\Segments;

use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Component;
use Mary\Traits\Toast;

class Labels extends Component
{
    use Toast;

    public ?Project $project;
    public Collection $tasks;
    public Collection $labels;
    public array $calendarEvents = [];

    public function mount(): void
    {
        if (isset($this->project)) {
            $this->labels = auth()
                ->user()
                ->labels()
                ->whereNotNull('project_id')
                ->where('project_id', $this->project->id)
                ->get();
            return;
        }
        $this->labels = auth()
            ->user()
            ->labels()
            ->whereNull('project_id')
            ->get();
    }

    public function render()
    {
        $isProject = isset($this->project);
        return view('livewire.segments.labels', ['title' => $isProject ? 'Project Labels' : 'Individual Labels'])
            ->layout($isProject ? 'layouts.project' : 'layouts.main');
    }
}
