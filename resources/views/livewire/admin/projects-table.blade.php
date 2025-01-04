<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
    #[Layout('layouts.admin')]
    class extends Component
    {
        use Toast, WithPagination;

        // filters
        public string $search = '';
        public int $owner_id;
        public int $status;

        // other
        public bool $drawer = false;
        public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
        public int $perPage = 10;

        public function goToProject(Project $project)
        {
            Auth::user()->impersonate($project->owner);
            return redirect()->route('project.show', $project);
        }

        public function updated($property): void
        {
            if (!is_array($property) && $property != '') {
                $this->resetPage();
            }
        }

        public function projects()
        {
            $output = Project::query()
                ->withAggregate('owner', 'username')
                ->when($this->search, function ($query) {
                    $query
                        ->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                })
                ->when(isset($this->owner_id), function ($query) {
                    $query->whereHas('owner', function ($query) {
                        $query->where('users.id', $this->owner_id);
                    });
                })
                ->when(isset($this->status), function ($query) {
                    $query->where('status', $this->status);
                })
                ->orderBy(...array_values($this->sortBy))
                ->paginate($this->perPage);
            return $output;
        }

        public function headers(): array
        {
            return [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'owner_username', 'label' => 'Owner'],
            ];
        }

        public function users(): Collection
        {
            return User::query()
                ->where('id', '!=', auth()->id())
                ->orderBy('username')
                ->get();
        }

        public function statuses(): array
        {
            return [
                ['id' => 1, 'name' => 'Pending'],
                ['id' => 2, 'name' => 'Completed'],
            ];
        }

        public function clear(): void
        {
            $this->reset();
            $this->resetPage();
            $this->success('Filters cleared.', position: 'toast-bottom');
        }

        public function with(): array
        {
            return [
                'users' => $this->users(),
                'headers' => $this->headers(),
                'statuses' => $this->statuses(),
                'projects' => $this->projects(),
            ];
        }

        public function deleteProject(Project $project)
        {
            $project->delete();
            $this->success('Project deleted successfully');
        }

        public function getFilterCount(): int
        {
            $filterCount = 0;
            if (!empty($this->search))
                $filterCount++;

            if (!empty($this->status))
                $filterCount++;

            if (!empty($this->owner_id))
                $filterCount++;

            return $filterCount;
        }
    };
?>
<div class="py-2">
    <x-header title="Projects" progress-indicator separator>
        <x-slot:actions>
            <x-input icon="o-bolt" placeholder="Search..."   wire:model.live.debounce="search" clearable/>
            <x-button label="Filters" icon="o-funnel" responsive :badge="$this->getFilterCount()" badge-classes="badge-primary badge-sm" @click="$wire.drawer = true"/>
        </x-slot:actions>
    </x-header>
    <x-table :headers="$headers" :rows="$projects" striped :sort-by="$sortBy" per-page="perPage" :per-page-values="[3,5,10]" with-pagination class="my-16">
        @scope('cell_status', $project)
            <span class="text-sm text-secondary">{{ $project->status->name }}</span>
        @endscope
        @scope('cell_owner', $project)
            <span class="text-sm text-secondary">{{ $project->owner->username }}</span>
        @endscope
        @scope('actions', $project)
            <x-dropdown icon="o-ellipsis-vertical" class="btn-sm btn-ghost overflow-y-visible">
                <x-menu-item title="See Project" icon="o-eye" class="text-secondary"  wire:click="goToProject({{$project->id}})"/>
                <x-menu-item title="Delete" icon="o-trash" class="text-error" wire:click="deleteProject({{$project->id}})"/>
            </x-dropdown>
        @endscope
        <x-slot:empty>
            <x-icon name="o-cube" label="No Projects found." />
        </x-slot:empty>
    </x-table>
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="min-w-[33vw]" >
        <div class="flex flex-col gap-2">
            <x-select label="Owner" icon="o-user" :options="$users" option-value="id" option-label="username" wire:model.live="owner_id" placeholder="All"/>
            <x-select label="Status" icon="o-information-circle" :options="$statuses" option-value="id" wire:model.live="status" option-label="name" placeholder="All"/>
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
