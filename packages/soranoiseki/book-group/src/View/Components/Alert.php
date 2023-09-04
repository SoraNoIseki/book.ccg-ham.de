<?php

namespace Soranoiseki\BookGroup\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{

    public $title = 'Info';

    public $content = '';
    
    public $type = 'Info';

    public $class = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        $type = 'Info',
        $class = ''
    )
    {
        $this->type = $type;
        $this->class = $class;
        $this->title = ucfirst($this->type);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('book-group::components.alert');
    }
}
