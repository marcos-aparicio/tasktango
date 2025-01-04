<?php

use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

enum Sorts: string
{
    case Default = 'Default';
    case Date = 'Date (Newest first)';
    case DateAscending = 'Date (Oldest first)';
    case Priority = 'Priority (Least important first)';
    case PriorityDescending = 'Priority (Most important first)';
};

enum GroupBy: string
{
    case None = 'None';
    case Label = 'Label';
    case Priority = 'Priority';
    case DueDate = 'Due Date';
    case SubProject = 'Sub Project';
    case Assignee = 'Assignee';
};

new
    #[Layout('layouts.project', ['useFlatpickr' => true])]
    class extends Component
    {
        use Toast;

        #[Locked]
        public Collection $allTasks;

        public Collection $filteredTasks;

        public Sorts $sort = Sorts::Default;

        public GroupBy $groupBy = GroupBy::None;

        public function updateGroupBy(string $groupBy): void
        {
            $this->groupBy = match ($groupBy) {
                'label' => GroupBy::Label,
                'priority' => GroupBy::Priority,
                'due_date' => GroupBy::DueDate,
                'assignee' => GroupBy::Assignee,
                'subproject' => GroupBy::SubProject,
                default => GroupBy::None,
            };
            $this->groupTasks();
        }

        public function updateSort(string $sort): void
        {
            $this->sort = match ($sort) {
                'date' => Sorts::Date,
                'date-asc' => Sorts::DateAscending,
                'priority' => Sorts::Priority,
                'priority-asc' => Sorts::Priority,
                'priority-desc' => Sorts::PriorityDescending,
                default => Sorts::Default,
            };
            $this->sortTasks();
        }

        public function sortTasks(): void
        {
            if (!isset($this->allTasks))
                return;
            if ($this->groupBy === GroupBy::None) {
                $this->filteredTasks = match ($this->sort) {
                    Sorts::Date => $this->filteredTasks->sortByDesc('due_date'),
                    Sorts::DateAscending => $this->filteredTasks->sortBy('due_date'),
                    Sorts::Priority => $this->filteredTasks->sortBy('priority'),
                    Sorts::PriorityDescending => $this->filteredTasks->sortByDesc('priority'),
                    default => $this->filteredTasks->sortBy([
                        ['priority', 'desc'],
                        ['due_date', 'desc']
                    ])
                };
                return;
            }

            $this->filteredTasks = $this->filteredTasks->map(function ($tasks) {
                return match ($this->sort) {
                    Sorts::Date => $tasks->sortByDesc('due_date'),
                    Sorts::DateAscending => $tasks->sortBy('due_date'),
                    Sorts::Priority => $tasks->sortBy('priority'),
                    Sorts::PriorityDescending => $tasks->sortByDesc('priority'),
                    default => $tasks->sortBy([
                        ['priority', 'desc'],
                        ['due_date', 'desc']
                    ])
                };
            });
        }

        public function groupTasks(): void
        {
            if (!isset($this->allTasks))
                return;

            if ($this->groupBy === GroupBy::None) {
                $this->filteredTasks = $this->allTasks;
                return;
            }

            $this->filteredTasks = collect($this->allTasks->groupBy(function ($task) {
                return match ($this->groupBy) {
                    GroupBy::Label => $task->labelsProject->pluck('name')->implode(', ') ?: 'No Labels',
                    GroupBy::Priority => $task->priority ?? 'No Priority',
                    GroupBy::DueDate => $task->due_date ? $task->due_date : 'No Due Date',
                    GroupBy::SubProject => $task->sub_project_id ? $task->subProject->name : 'No Sub Project',
                    GroupBy::Assignee => $task->assignee ? $task->assignee->username : 'No Assignee',
                    default => 'Ungrouped',
                };
            }))->sortKeys();
        }

        #[Url]
        public ?string $name;

        #[Url]
        public bool $no_due_date = false;

        #[Url]
        public bool $overdue = false;

        #[Url]
        public bool $any_subproject = false;

        #[Url]
        public bool $any_assignee = true;

        #[Url]
        public bool $include_any_or_all_project_labels = true;

        #[Locked]
        public Project $project;

        #[Url]
        public ?int $priority = null;

        #[Url]
        public ?int $assignee_id = null;

        #[Url]
        public ?int $subproject_id = null;

        #[Url]
        public ?int $frequency = null;

        #[Url]
        public bool $all_projects = true;

        #[Url]
        public ?string $due_date = null;

        public ?array $selected_project_labels = [];

        public function mount()
        {
            // to avoid problems
            $this->reset(['selected_project_labels']);
        }

        public function clearParams()
        {
            $this->reset([
                'overdue',
                'priority',
                'assignee_id',
                'frequency',
                'due_date',
                'selected_project_labels',
                'include_any_or_all_project_labels',
                'no_due_date',
            ]);
        }

        public function search(): void
        {
            $this->validate([
                'overdue' => 'boolean',
                'priority' => 'nullable|integer',
                'frequency' => 'nullable|integer',
                'due_date' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value && strpos($value, ' to ') !== false) {
                            $dates = explode(' to ', $value);
                            if (count($dates) !== 2 || !strtotime($dates[0]) || !strtotime($dates[1])) {
                                $fail('The due date is not a valid date range.');
                            }
                        } elseif ($value && !strtotime($value)) {
                            $fail('The due date is not a valid date.');
                        }
                    },
                ],
                'selected_project_labels' => 'nullable|array',
                'selected_project_labels.*' => [
                    'integer',
                    function ($attribute, $value, $fail) {
                        if (!in_array($value, $this->project->labels->pluck('id')->toArray())) {
                            $fail('The selected label ID ' . $value . ' is not valid for this project.');
                        }
                    },
                ],
                'include_any_or_all_project_labels' => 'boolean',
                'no_due_date' => 'boolean',
                'any_assignee' => 'boolean',
                'assignee_id' => ['nullable', 'integer',
                    function ($attribute, $value, $fail) {
                        if ($value && !in_array($value, $this->project->users->pluck('id')->toArray())) {
                            $fail('The selected assignee ID ' . $value . ' is not valid for this project.');
                        }
                    }],
            ], [
                'due_date.date' => 'The due date is not a valid date. Type it again, sometimes is an error within the URL',
            ]);

            if ($this->no_due_date && $this->due_date) {
                $this->addError('no_due_date', 'Cannot have a due date and no due date at the same time.');
                return;
            }

            if ($this->overdue && $this->due_date) {
                $this->addError('overdue', 'Cannot have a due date and be overdue at the same time.');
                return;
            }

            $name = $this->name ?? '';
            $query = $this
                ->project
                ->tasks()
                ->pending()
                ->when($name !== '', fn($q) => $q
                    ->whereAny(['name', 'description'], 'LIKE', "%$name%"))
                ->when(
                    $this->priority, fn($q) => $q->where('priority', $this->priority)
                )
                ->when(
                    $this->frequency, fn($q) => $q->where('frequency', $this->frequency)
                )
                ->when(
                    $this->due_date,
                    function ($q) {
                        if (strpos($this->due_date, ' to ') === false)
                            return $q->where('due_date', $this->due_date);

                        return $q->whereBetween('due_date', explode(' to ', $this->due_date));
                    },
                )
                ->when(
                    $this->selected_project_labels && count($this->selected_project_labels),
                    fn($q) =>
                        $this->include_any_or_all_project_labels
                            ? $q->whereHas('labels', fn($q) => $q->whereIn('labels.id', $this->selected_project_labels))
                            : $q->whereHas('labels',
                                function ($q) {
                                    $q
                                        ->select(DB::raw('count( distinct labels.id ) as label_count'))
                                        ->whereIn('labels.id', $this->selected_project_labels);
                                }, '=', count($this->selected_project_labels)),
                )
                ->when(count($this->selected_project_labels) <= 0,
                    fn($q) => $this->include_any_or_all_project_labels ? $q : $q->whereDoesntHave('labels'))
                ->when($this->assignee_id !== null && !$this->any_assignee, fn($q) => $q->where('assignee_user_id', $this->assignee_id))
                ->when($this->assignee_id === null && !$this->any_assignee, fn($q) => $q->whereNull('assignee_user_id'))
                ->when($this->subproject_id === null && !$this->any_subproject, fn($q) => $q->whereNull('sub_project_id'))
                ->when($this->subproject_id !== null && !$this->any_subproject, fn($q) => $q->where('sub_project_id', $this->subproject_id));

            if ($this->overdue)
                $query = $query->where('due_date', '<', today());
            if ($this->no_due_date)
                $query = $query->whereNull('due_date');

            $this->allTasks = $query->get();

            $this->groupTasks();
            $this->sortTasks();
        }

        #[On(['task-created', 'task-deleted', 'task-completed', 'task-uncompleted', 'task-updated'])]
        public function refreshTasks(): void
        {
            $this->search();
        }

        public function clearDueDate(): void
        {
            $this->due_date = null;
        }

        public function updatedOverdue($value): void
        {
            if ($value)
                $this->reset('due_date', 'no_due_date');
        }

        public function updatedNoDueDate($value): void
        {
            if (!$value)
                return;
            $this->reset('due_date', 'overdue');
        }
    };
?>
@php
$dateConfig = [ 'enableTime' => false, 'mode' => 'range' ]
@endphp
<div class="flex flex-col gap-4 h-full">
    <x-form class="search-params sticky top-0 z-[60] py-4 bg-base-100" wire:submit="search">
        <x-input type="text" placeholder="Query" icon="o-magnifying-glass" dusk="task-name-search" class="font-bold text-xl" wire:model="name" />
        <div class="collapse bg-base-300 overflow-visible shadow-base-300">
          <input type="checkbox" />
          <div class="collapse-title text-xl font-medium">Filters</div>
          <div class="collapse-content flex flex-col md:flex-row gap-4">
            <div class="flex flex-col gap-2">
                <livewire:task.subcomps.priority wire:model="priority" bigger/>
                <livewire:task.subcomps.frequency wire:model="frequency" bigger/>
            </div>
            <div class="flex flex-col gap-4">
                <livewire:task.subcomps.assignee wire:model.live="assignee_id" :$project bigger/>
                <x-checkbox label="Any assignee" wire:model.live="any_assignee"/>
            </div>
            <div>
                <livewire:task.subcomps.project-labels wire:model="selected_project_labels" :$project doNotCreate/>
                <label class="label cursor-pointer flex gap-4">
                  <span class="label-text">Match all selected labels</span>
                  <input type="checkbox" class="toggle" wire:model="include_any_or_all_project_labels" />
                  <span class="label-text">Match any selected labels</span>
                </label>
            </div>
            <div class="flex-1 flex flex-col gap-4">
                <x-datepicker label="Due" wire:model.live="due_date" dusk="due-date-search" :config="$dateConfig"/>
                <div class="flex gap-4 items-center">
                    <x-button class="btn-secondary btn-sm" label="Clear Due Date" wire:click="clearDueDate" x-show="$wire.due_date !== null"/>
                    <x-checkbox label="Overdue" wire:model.live="overdue"/>
                    <x-checkbox label="No Due Date" wire:model.live="no_due_date"/>
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <livewire:task.subcomps.subproject wire:model="subproject_id" :$project doNotCreate/>
                <x-checkbox label="Any Subproject" wire:model.live="any_subproject"/>
            </div>
          </div>
        </div>
        @php
            $title = $sort->value;
            $groupTitle = $groupBy->value;
        @endphp
        <div>
            <x-custom-dropdown :label="'Group by: '.$groupTitle" class="btn-ghost btn-sm" dropdown-class="bg-base-300">
                <x-menu-item title="None" wire:click="updateGroupBy('none')" />
                <x-menu-item title="Label" wire:click="updateGroupBy('label')"/>
                <x-menu-item title="Priority" wire:click="updateGroupBy('priority')"/>
                <x-menu-item title="Due Date" wire:click="updateGroupBy('due_date')"/>
                <x-menu-item title="SubProject" wire:click="updateGroupBy('subproject')"/>
                <x-menu-item title="Assignee" wire:click="updateGroupBy('assignee')"/>
            </x-custom-dropdown>
            <x-custom-dropdown :label="'Sort by: '.$title" class="btn-ghost btn-sm" dropdown-class="bg-base-300">
                <x-menu-item title="Default" wire:click="updateSort('')" />
                <x-menu-item title="Due Date (Newest first)" wire:click="updateSort('date')"/>
                <x-menu-item title="Due Date (Oldest first)" wire:click="updateSort('date-asc')"/>
                <x-menu-item title="Priority (Least important first)" wire:click="updateSort('priority-asc')"/>
                <x-menu-item title="Priority (Most important first)" wire:click="updateSort('priority-desc')"/>
            </x-custom-dropdown>
            <x-button icon="o-x-mark" class="btn-ghost" label="Clear Filters" wire:click="clearParams"/>
            <x-button icon="o-magnifying-glass" class="btn-primary" label="Search" type="submit"/>
        </div>
    </x-form>

    <div class="flex flex-col gap-2">
        @isset($filteredTasks)
        @if($groupBy !== GroupBy::None)
            @foreach($filteredTasks as $group => $tasks)
                <div class="border-transparent border-b-base-300 border-b-2 rounded-none collapse collapse-arrow overflow-visible" dusk="group-title-[{{$group}}]"
            wire:key="group-title-{{$group}}">
              <input type="checkbox" />
               <div @class(['collapse-title text-xl'])>
                    <p class="text-sm">
                        {{$group}}: {{ $tasks->count() }} task(s)
                    </p>
                </div>
                <div class="collapse-content flex justify-center flex-col items-center  gap-2">
                @forelse($tasks as $task)
                    <livewire:task.card :$task wire:key="pending-{{ $task->id }}-group-{{$group}}" />
                @empty
                    <div class="my-4 flex flex-col items-center w-full" wire:key="empty-{{$group}}">
                        <span class="text-2xl font-bold">🤷 It seems empty around here</span>
                        <span>You've got no tasks! Add one</span>
                    </div>
                @endforelse
                </div>
            </div>
            @endforeach
        @else
            <div>Tasks found: {{$filteredTasks->count()}}</div>
            @forelse($filteredTasks as $task)
                <livewire:task.card :$task wire:key="ungrouped-search-{{$task->id}}"/>
            @empty
            <div>
                No tasks found matching your criteria.
            </div>
            @endforelse
        @endif
        @endisset
    </div>
    {{-- task modal ready --}}
    <livewire:task.modal />
</div>
