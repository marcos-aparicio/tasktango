<?php

Use App\Models\User;
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

        public string $search = '';
        public array $sortBy = ['column' => 'username', 'direction' => 'asc'];
        public int $perPage = 10;

        // HOW TO IMPLEMENT ACTING AS - IMPERSONATION
        public function updated($property): void
        {
            if (!is_array($property) && $property != '') {
                $this->resetPage();
            }
        }

        public function users()
        {
            return User::where('id', '!=', auth()->id())
                ->when($this->search, function ($query) {
                    $query
                        ->where('username', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('full_name', 'like', '%' . $this->search . '%');
                })
                ->orderBy(...array_values($this->sortBy))
                ->paginate($this->perPage);
        }

        public function headers(): array
        {
            return [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'profile_picture', 'label' => 'Avatar', 'class' => 'max-sm:hidden w-1 '],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'username', 'label' => 'Username'],
                ['key' => 'full_name', 'label' => 'Full Name'],
            ];
        }

        public function with(): array
        {
            return [
                'headers' => $this->headers(),
                'users' => $this->users(),
            ];
        }

        public function deleteUser(User $user)
        {
            if ($user->id === auth()->id()) {
                $this->error('You cannot delete yourself');
                return;
            }
            $user->delete();
            $this->success('User deleted successfully');
        }
    };
?>
<div class="py-2">
    <x-header title="Users" progress-indicator separator>
        <x-slot:actions>
            <x-input icon="o-bolt" placeholder="Search..."   wire:model.live.debounce="search" clearable/>
        </x-slot:actions>
    </x-header>
    <x-table :headers="$headers" :rows="$users" striped :sort-by="$sortBy" per-page="perPage" :per-page-values="[3,5,10]" with-pagination class="my-16">
        @scope('cell_profile_picture', $user)
            <x-avatar-or-icon :user="$user" avatar-class="!w-5 !h-5"
                icon-class="!w-5 !h-5 text-primary rounded-full"
            />
        @endscope
        @scope('actions', $user)
            <x-dropdown icon="o-ellipsis-vertical" class="btn-sm btn-ghost overflow-y-visible">
                <x-menu-item title="See Tasks" icon="o-eye" class="text-secondary" link="{{route('impersonate',$user->id)}}"/>
                <x-menu-item title="Delete" icon="o-trash" class="text-error" wire:click="deleteUser({{$user->id}})"/>
            </x-dropdown>
        @endscope
    </x-table>
</div>
