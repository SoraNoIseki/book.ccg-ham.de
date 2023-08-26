<?php

namespace App\Http\Livewire\Library;

use Livewire\Component;
use Livewire\WithPagination;

use Soranoiseki\Library\Models\Book;

class ShowBooks extends Component
{
    use WithPagination;

    public $search = '';

    protected $listeners = ['refreshBookList' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $books = Book::with(['copies' => function($query) {
                $query->orderBy('copyid', 'asc');
            }])->orderBy('bibid', 'asc')
            ->where('title', 'like', '%'.$this->search.'%')
            ->orWhere('call_nmbr1', 'like', '%'.$this->search.'%')
            ->orderBy('bibid', 'asc')
            ->paginate(10);

        return view('livewire.library.show-books', [
            'books' => $books,
        ]);
    }

    public function onClickCopy($bookId, $copyId, $memberId = null) 
    {
        if (!$memberId) {
            $this->emit('openModal', 'library.borrow-book', ['bookId' => $bookId, 'copyId' => $copyId]);
        } else {
            $this->emit('openModal', 'library.return-book', ['bookId' => $bookId, 'copyId' => $copyId]);
        }
        
    }
    
}
