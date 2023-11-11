<?php

namespace Soranoiseki\BookGroup\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PdfLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('book-group::layouts.pdf');
    }
}
