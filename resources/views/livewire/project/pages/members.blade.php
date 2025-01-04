<?php

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Traits\HandlesAuthorization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
    #[Layout('layouts.project')]
    class extends Component
    {
        use Toast, WithPagination;
        // my traits
        use HandlesAuthorization;

        public bool $showRemoveUserModal = true;

        #[Locked]
        public User $userToRemove;

        public int $perPage = 10;

        public Project $project;

        public array $sortBy = ['column' => 'username', 'direction' => 'asc'];

        // Reset pagination when any component property changes
        public function updated($property): void
        {
            if (!is_array($property) && $property != '') {
                $this->resetPage();
            }
        }

        public function users(): LengthAwarePaginator
        {
            return $this
                ->project
                ->users()
                ->orderBy(...array_values($this->sortBy))
                ->paginate($this->perPage);
        }

        public function upgradeOtherUserRole(User $userToUpgradeRole)
        {
            $message = $this->authorizeOrFail('upgradeOtherUserRole', [$this->project, $userToUpgradeRole]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }

            $this->project->changeUserRole($userToUpgradeRole, upgrade: true);
            $this->success(title: 'User role upgraded successfully');
        }

        public function downgradeOtherUserRole(User $userToDowngradeRole)
        {
            $message = $this->authorizeOrFail('downgradeOtherUserRole', [$this->project, $userToDowngradeRole]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }

            $this->project->changeUserRole($userToDowngradeRole, upgrade: false);
            $this->success(title: 'User role downgraded successfully');
        }

        public function removeUserFromProject(User $userToRemove)
        {
            $message = $this->authorizeOrFail('removeUserFromProject', [$this->project, $userToRemove]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            $this->project->removeUser($userToRemove);
            $this->showRemoveUserModal = false;
            $this->success(title: 'User removed successfully');
        }

        public function confirmMemberRemoval(User $userToRemove)
        {
            $this->showRemoveUserModal = true;
            $this->userToRemove = $userToRemove;
        }

        public function cancelMemberRemoval()
        {
            $this->showRemoveUserModal = false;
            unset($this->userToRemove);
        }
    };

?>
@php
    $users = $this->users();
    $headers = [
        ['key' => 'id', 'label' => '#', 'class' => 'w-1 text-primary hidden'],
        ['key' => 'profile_picture', 'label' => 'Avatar', 'class' => 'max-sm:hidden w-1 '],
        ['key' => 'username', 'label' => 'Nickname' ],
        ['key' => 'full_name', 'label' => 'Full Name', 'class'=>'max-md:hidden' ],
        ['key' => 'email', 'label' => 'Email','class'=>'max-md:hidden' ],
        ['key' => 'role', 'label' => 'Role' ],
    ];

    $row_decoration = [
        'text-secondary font-bold' => fn($user) => $user->id === auth()->id(),
    ];

@endphp

<div class="flex flex-col gap-8 h-full overflow-hidden flex-1 min-h-svh">
    <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight sticky top-5 z-10 p-4">Project Members</h2>
    <x-table :headers="$headers" :rows="$users" striped per-page="perPage" :per-page-values="[3,5,10]" :with-pagination="$this->project->users->count() > 10" :row-decoration="$row_decoration" :sort-by="$sortBy" class="my-16">
    @scope('cell_profile_picture', $user)
        <x-avatar-or-icon :user="$user" avatar-class="!w-5 !h-5"
            icon-class="!w-5 !h-5 text-primary rounded-full"
        />
    @endscope
    @scope('cell_role',$user)
        @php
            $roleName = $this->project->getRoleName($user);
        @endphp
        <span>
            {{ $roleName }}
        </span>
    @endscope
    @scope('actions',$user, $project)
        @canany(['upgradeOtherUserRole', 'downgradeOtherUserRole'], [$project, $user])
        <x-dropdown>
            @can('upgradeOtherUserRole', [$project, $user])
                <x-menu-item title="Upgrade Role" icon="o-arrow-up-circle" class="text-success" @click="$wire.upgradeOtherUserRole({{$user->id}})" />
            @endcan
            @can('downgradeOtherUserRole', [$project, $user])
                <x-menu-item title="Downgrade Role" icon="o-arrow-down-circle" class="text-warning" @click="$wire.downgradeOtherUserRole({{$user->id}})"/>
            @endcan
            @php
            @endphp
            @can('removeUserFromProject', [$project, $user])
                <x-menu-item title="Remove User" icon="o-trash" class="text-error" wire:click="confirmMemberRemoval({{$user->id}})"/>
            @endcan
        </x-dropdown>
        @endcanany
    @endscope
    </x-table>
    @isset($userToRemove)
    @can('removeUserFromProject', [$project, $userToRemove])
        <x-modal title="Remove User" wire:model="showRemoveUserModal">
            <div>Are you sure you want to delete this user from the project?</div>
            <div class="text-primary font-bold">{{$userToRemove->username}}</div>
            <x-slot:actions>
                <x-button label="Cancel" wire:click="cancelMemberRemoval()" />
                <x-button label="Confirm" class="btn-primary" wire:click="removeUserFromProject({{$userToRemove->id}})"/>
            </x-slot:actions>
        </x-modal>
    @endcan
    @endisset
</div>
