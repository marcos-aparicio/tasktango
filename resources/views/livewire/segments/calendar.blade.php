<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
    document.addEventListener('livewire:initialized', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            dayMaxEvents: true,
            height: '100%',
            eventOrder:"start,-duration,allDay,overdue,title",
            initialView: 'dayGridMonth',
            eventStartEditable: true,
            dateClick: function(info) {
                let today = new Date();
                today.setHours(0, 0, 0, 0);
                if (info.date < today) {
                    return;
                }
                if(info.date < today) return;
                @this.dispatch('open-task-modal', {
                    task_id: -1,
                    calendar_trigger: true,
                    prefilled: {
                        due_date: info.dateStr,
                        project: {{ $project->id ?? 'null' }},
                    }
                });

            },
            eventClick: function(info) {
                const morePopOver = document.querySelector('.fc-more-popover');
                if(morePopOver)
                    morePopOver.style.display = 'none';
                const taskId = info.event.id;
                @this.dispatch('open-task-modal', {
                    task_id: taskId,
                    calendar_trigger: true
                });
            },
            eventDrop: function(info) {
                let today = new Date();
                today.setHours(0, 0, 0, 0);
                if (info.event.start < today) {
                    info.revert();
                    @this.error('Cannot set task to be due in the past!');
                    return;
                }
                const taskId = info.event.id;
                @this.updateTaskDueDate(taskId, info.event.startStr);
            },
        });
        calendar.addEventSource(@json($calendarEvents));

        const updateEvents = () => {
            @this.setEvents().then(() => {
                // Remove the existing event source
                calendar.getEventSources()[0].remove();

                                // Add the new event source
                calendar.addEventSource(@this.calendarEvents);
                            // Detect and remove duplicate events
                const events = calendar.getEvents();
                const eventIds = new Set();
                events.forEach(event => {
                    if (eventIds.has(event.id)) {
                        event.remove();
                    } else {
                        eventIds.add(event.id);
                    }
                });
            });
        };
        document.addEventListener('task-deleted', updateEvents);
        document.addEventListener('task-completed', updateEvents);
        document.addEventListener('task-updated', updateEvents);
        document.addEventListener('task-created', updateEvents);
        calendar.render();
    });
</script>
<div class="flex flex-col gap-4 h-full">
    <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight pb-4 sticky top-5 z-10">Calendar</h2>
    <div dusk="calendar-body" class="flex-1 overflow-y-scroll">
        <div wire:ignore id='calendar'></div>
    </div>
    {{-- task modal ready --}}
    <livewire:task.modal />
</div>
