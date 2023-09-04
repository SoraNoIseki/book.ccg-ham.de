<?php

namespace Soranoiseki\BookGroup\View\Components\Tabs;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Label extends Component
{
    public $id = '';

    public $label = '';

    /**
     * Create a new component instance.
     */
    public function __construct($id = 'tab', $label = 'Tab')
    {
        $this->id = $id;
        $this->label = $label;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('book-group::components.tabs.label');
    }
}
