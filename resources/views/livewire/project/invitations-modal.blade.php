<?php

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Traits\HandlesAuthorization;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    // my traits
    use HandlesAuthorization;

    public bool $openTaskModal = false;

    #[On('project-invitation-modal-open')]
    public function showModal(Project $project): void
    {
        // TODO:check if the project is valid both in create invitation and here, also check that the user has permissions to create an invitation and to see this project
        $this->project = $project;
        $this->openTaskModal = true;
    }

    public function processInvitation(int $id, bool $acceptOrNo): void
    {
        if (!auth()->check()) {
            $this->error('You must be logged in to accept an invitation');
            return;
        }

        $invitation = ProjectInvitation::find($id);

        if (!$invitation) {
            $this->addError('error', 'Invitation not found');
            return;
        }

        $message = $this->authorizeOrFail('update', [ProjectInvitation::class, $invitation]);
        if ($message) {
            $this->addError('error', $message);
            return;
        }

        $projectID = $invitation->project->id ?? null;

        auth()->user()->processInvitation($invitation, $acceptOrNo);
        $this->resetValidation();

        if (!$acceptOrNo) {
            $this->success(title: 'Invitation declined');
            return;
        }
        $this->success(
            title: 'Invitation accepted',
            position: 'toast-bottom toast-end text-wrap',
            description: 'You have been added to the project',
            redirectTo: route('project.show', $projectID)
        );
    }
};

?>

<x-modal boxClass="max-w-3xl flex flex-col pt-0 px-0 relative" wire:model="openTaskModal">
    {{-- modal's header --}}
    <div class="flex justify-between p-4 pb-2">
        <p>Project Invitations</p>
        <x-button icon="o-x-mark" class="btn-ghost btn-circle btn-xs" @click="$wire.openTaskModal = false;"responsive/>
    </div>
    <div class="divider divider-primary my-0 h-fit"></div>
    {{-- modal's body --}}
    <div class="flex flex-col gap-4 p-4 overflow-hidden" wire:submit="createInvitation">
        @php
            $user = auth()->user();
            $invitations = $user->receivedProjectInvitationsValid();
            $count = $invitations->count();
        @endphp
        <span class="text-sm text-secondary">You have {{$count}} pending invitations</span>
        <x-errors  icon="o-face-frown" />
        <div class="flex flex-wrap justify-center gap-4 overflow-y-scroll">
        @foreach(auth()->user()->receivedProjectInvitationsValid()->get() as $invitation)
            <div class="flex flex-col gap-2 p-2 px-4 bg-base-200 rounded shadow-md">
                <span>
                    <span class="font-bold text-primary">{{$invitation->inviter->username}}</span>
                    has invited you to join the project
                    <span class="font-bold text-primary"> {{$invitation->project->name}} </span>
                </span>
                @isset($invitation->valid_until)
                    <span class="text-sm">Invitation expires on {{$invitation->valid_until->diffForHumans()}}</span>
                @else
                    <span class="text-sm">Invitation never expires</span>
                @endisset
                <div class="flex gap-2">
                    <x-button label="Accept" icon="o-check" class="btn-primary btn-sm" wire:click="processInvitation({{$invitation->id}},true)" responsive/>
                    <x-button label="Decline" icon="o-x-mark" class="btn-error btn-sm" wire:click="processInvitation({{$invitation->id}},false)" responsive/>
                </div>
            </div>
        @endforeach
        </div>
    </div>
</x-modal>
