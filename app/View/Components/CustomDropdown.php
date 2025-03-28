<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Closure;
use Exception;

// code extracted from the original Choices component:
// https://github.com/robsontenorio/mary/blob/main/src/View/Components/Dropdown.php
// in order to modify it to suit my needs
class CustomDropdown extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $label = null,
        public ?string $icon = 'o-chevron-down',
        public ?bool $right = false,
        public ?bool $top = false,
        public ?bool $noXAnchor = false,
        // Slots
        public mixed $trigger = null
    ) {
        $this->uuid = 'mary' . md5(serialize($this));
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <details
                    x-data="{open: false}"
                    @click.outside="open = false"
                    :open="open"
                    @class([
                        'dropdown',
                        'dropdown-end' => ($noXAnchor && $right),
                        'dropdown-top' => ($noXAnchor && $top),
                        'dropdown-bottom' => $noXAnchor,
                    ])
                >
                    <!-- CUSTOM TRIGGER -->
                    @if($trigger)
                        <summary x-ref="button" @click.prevent="open = !open" {{ $trigger->attributes->class(['list-none']) }}>
                            {{ $trigger }}
                        </summary>
                    @else
                        <!-- DEFAULT TRIGGER -->
                        <summary x-ref="button" @click.prevent="open = !open" {{ $attributes->class(["btn normal-case"]) }}>
                            {{ $label }}
                            <x-mary-icon :name="$icon" />
                        </summary>
                    @endif

                    <ul
                        @class([
                            'p-2','shadow','menu','z-[200]','border','border-base-200','bg-base-100','dark:bg-base-200','rounded-box','w-auto','min-w-max', $attributes->get('dropdown-class'),
                            'dropdown-content' => $noXAnchor,
                        ])
                        @click="open = false"
                        @if(!$noXAnchor)
                            x-anchor.{{ $right ? 'bottom-end' : 'bottom-start' }}="$refs.button"
                        @endif
                    >
                        <div wire:key="dropdown-slot-{{ $uuid }}">
                            {{ $slot }}
                        </div>
                    </ul>
                </details>
            HTML;
    }
}
