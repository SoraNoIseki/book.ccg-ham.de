<?php

namespace Soranoiseki\BookGroup\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Soranoiseki\BookGroup\Models\Worship\Song;

class SongSelector extends Component
{
    public $songs;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $target,
    )
    {
        $this->songs = Song::orderBy('song_id', 'ASC')->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('book-group::components.song-selector');
    }
}
