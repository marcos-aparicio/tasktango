<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Closure;

class CustomMenuSub extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public ?string $badgeClasses = null,
        public bool $open = false,
        public ?bool $enabled = true,
    ) {
        $this->uuid = 'mary' . md5(serialize($this));
    }

    public function render(): View|Closure|string
    {
        if ($this->enabled === false) {
            return '';
        }

        return <<<'HTML'
                @aware(['activeBgColor' => 'bg-base-300'])

                @php
                    $submenuActive = Str::contains($slot, 'mary-active-menu');
                @endphp

                <li
                    {{$attributes->merge(['class' => 'menu-sub'])}}j
                    x-data="
                    {
                        show: @if($submenuActive || $open) true @else false @endif,
                        toggle(){
                            // From parent Sidebar
                            if (this.collapsed) {
                                this.show = true
                                $dispatch('menu-sub-clicked');
                                return
                            }

                            this.show = !this.show
                        }
                    }"
                >
                    <details :open="show" @if($submenuActive) open @endif @click.stop>
                        <summary @click.prevent="toggle()" @class(["hover:text-inherit text-inherit", $activeBgColor => $submenuActive])>
                            @if($icon)
                                <x-mary-icon :name="$icon" class="inline-flex"  />
                            @endif
                            <span class="mary-hideable">{{ $title }}</span>
                            @if($badge)
                            <span class="badge badge-ghost badge-sm {{ $badgeClasses }}">{{ $badge }}</span>
                            @endif
                        </summary>
                        <ul class="mary-hideable">
                            {{ $slot }}
                        </ul>
                    </details>
                </li>
            HTML;
    }
}
