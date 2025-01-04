<?php

namespace App\Livewire\Segments;

use App\Models\Label as ModelLabel;
use App\Models\Project;

class Label extends BaseSegment
{
    public ModelLabel $label;
    public ?Project $project;

    protected function setTitle(): string
    {
        return '🏷️ Label: ' . $this->label->name;
    }

    protected function taskFilteringCriteria()
    {
        return $this->label->tasks();
    }

    protected function setPrefilledData(): array
    {
        if (isset($this->project)) {
            return [
                'selected_project_labels' => [$this->label->id],
            ];
        }

        return [
            'selected_individual_labels' => [$this->label->id],
        ];
    }
}
