<?php

namespace App\Http\Livewire\Library;

use LivewireUI\Modal\ModalComponent;
use Soranoiseki\Library\Models\Copy;
use Soranoiseki\Library\Models\Member;

class ReturnBook extends ModalComponent
{
    public $copy;

    public $book;

    public $copyId;

    public $bookId;

    public $member;

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
    
    public function mount($bookId, $copyId)
    {
        $this->copy = Copy::where('bibid', $bookId)->where('copyid', $copyId)->firstOrFail();
        $this->book = $this->copy->book;
        $this->copyId = $copyId;
        $this->bookId = $bookId;
        $this->member = $this->copy->member;
    }

    public function render()
    {
        return view('livewire.library.return-book');
    }

    public function updated()
    {
    }

    public function submit()
    {
        // TODO: move to service or model
        Copy::where('bibid', $this->bookId)->where('copyid', $this->copyId)->update([
            'status_cd' => 'in',
            'status_begin_dt' => now(),
            'due_back_dt' => null,
            'mbrid' => 0,
            'renewal_count' => 0,
        ]);
        $this->emit('refreshBookList');
        $this->closeModal();
    }

    public function close()
    {
        $this->closeModal();
    }
}
