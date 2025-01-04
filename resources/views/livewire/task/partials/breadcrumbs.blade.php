<div class="flex items-center gap-2">
    <div class="breadcrumbs text-sm p-0">
        <ul>
            <li>
                @if(isset($task->project->name))
                @php
                    $label = $task->project->name;
                    if(strlen($label) > 20)
                        $label = substr($label,0,20).'...';

                    if(isset($task->subproject->name))
                        $label .= ' / '.$task->subproject->name;

                @endphp
                    <x-button :$label icon="o-folder" class="btn-sm btn-ghost" link="{{route('project.show',$task->project->id)}}"/>
                @elseif(isset($form->project) && $project = \App\Models\Project::find($form->project))
                    <x-button :label="$project->name" icon="o-folder" class="btn-sm btn-ghost" link="{{route('project.show',$project->id)}}"/>
                @else
                    <x-button label="Inbox" icon="o-folder-arrow-down" class="btn-sm btn-ghost" link="{{route('inbox')}}"/>
                @endif
            </li>
            @if(isset($task) && $task->depth() > 1)
                <li>
                    <span class="pointer-events-none">...</span>
                </li>
            @endif
            @isset($task->taskParent)
                <li>
                    <x-button :label="$task->taskParent->name" @click="$wire.openTaskParentModal"  class="overflow-hidden max-w-xs"/>
                </li>
            @endisset
        </ul>
    </div>
</div>

