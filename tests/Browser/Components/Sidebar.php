<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Sidebar extends BaseComponent
{
    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '#main-sidebar';
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
            '@profile-avatar' => '@profile-avatar',
            '@theme-controller' => '@theme-controller',
            '@next-7-days' => '@next-7-days',
            '@logout' => '@logout',
            '@inbox' => '@inbox',
        ];
    }

    public function toggleTheme(Browser $browser): void
    {
        $browser
            ->click('@theme-controller')
            ->pause(400);
    }
}
