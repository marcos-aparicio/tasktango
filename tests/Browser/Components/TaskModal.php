<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class TaskModal extends BaseComponent
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@task-modal';
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
            '@close' => '@close',
            '@date' => '@date',
            '@name' => '@name',
            '@description' => '@description',
            '@project' => '@project',
            '@individual-labels' => '[dusk="main-attributes"] [dusk="individual-labels"]'
        ];
    }
}
