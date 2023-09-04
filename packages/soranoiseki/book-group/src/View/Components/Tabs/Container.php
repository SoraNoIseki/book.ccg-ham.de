<?php

namespace Soranoiseki\BookGroup\View\Components\Tabs;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Container extends Component
{

    public $defaultTab = '';

    public $labels = '';

    public $tabs = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        $defaultTab = 'tab', 
        $labels = 'Is any Label here?', 
        $tabs = 'I want some Tabs'
    )
    {
        $this->defaultTab = $defaultTab;
        $this->labels = $labels;
        $this->tabs = $tabs;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('book-group::components.tabs.container');
    }
}
