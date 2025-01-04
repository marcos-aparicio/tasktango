<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class TaskCard extends BaseComponent
{
    private int $task_id;

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@task-card-' . $this->task_id;
    }

    public function __construct(int $task_id)
    {
        $this->task_id = $task_id;
    }

    /**
     * Assert that the browser page contains the component.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertVisible($this->selector());
    }

    /**
     * Get the element shortcuts for the component.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@checkbox' => '@checkbox',
        ];
    }
}
