<?php

namespace App\Http\Livewire\Library;

use Livewire\Component;
use Livewire\WithPagination;

use Soranoiseki\BookGroup\Models\Library\Book;

class ShowBooks extends Component
{
    use WithPagination;

    public $search = '';

    public $returnMode = false;

    public $externalMode = false;

    protected $listeners = ['refreshBookList' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $books = Book::with(['copies' => function($query) {
                $query->orderBy('copyid', 'asc');
            }])
            ->where(function($whereQuery) {
                $whereQuery->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('call_nmbr1', 'like', '%'.$this->search.'%')
                ->orWhere('author', 'like', '%'.$this->search.'%');
            })
            ->when($this->returnMode, function($whenQuery) {
                $whenQuery->whereRelation('copies', 'mbrid', '>', 0);
            })
            ->when($this->externalMode, function($whenQuery) {
                $whenQuery->where('call_nmbr1', 'like', 'FMCD%');
            })
            ->orderByRaw('LENGTH(call_nmbr1), call_nmbr1')
            ->paginate(15);

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

    public function toggleReturnMode() {
        $this->returnMode = !$this->returnMode;
        $this->resetPage();
    }

    public function toggleExternalMode() {
        $this->externalMode = !$this->externalMode;
        $this->resetPage();
    }
    
}
