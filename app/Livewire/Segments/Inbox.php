<?php

namespace App\Livewire\Segments;

class Inbox extends BaseSegment
{
    protected function setPrefilledData(): array
    {
        return [];
    }

    protected function setTitle(): string
    {
        return 'Inbox';
    }

    protected function taskFilteringCriteria(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return auth()
            ->user()
            ->tasks()
            ->whereNull('project_id');
    }
}
