<?php

namespace App\Http\Livewire\Library;

use LivewireUI\Modal\ModalComponent;
use Soranoiseki\Library\Models\Copy;
use Soranoiseki\Library\Models\Member;

class BorrowBook extends ModalComponent
{
    public $copy;

    public $book;

    public $copyId;

    public $bookId;

    public $members;

    public $selectedMember;

    protected $rules = [
        'selectedMember' => 'required',
    ];

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
    
    public function mount($bookId, $copyId)
    {
        $this->copy = Copy::where('bibid', $bookId)->where('copyid', $copyId)->first();
        $this->book = $this->copy->book;
        $this->copyId = $copyId;
        $this->bookId = $bookId;
        $this->members = Member::all()->pluck('full_name', 'mbrid');
    }

    public function render()
    {
        return view('livewire.library.borrow-book');
    }

    public function updated()
    {
        $this->validate();
    }

    public function submit()
    {
        $this->validate();
        // TODO: move to service or model
        Copy::where('bibid', $this->bookId)->where('copyid', $this->copyId)->update([
            'status_cd' => 'out',
            'status_begin_dt' => now(),
            'due_back_dt' => now()->addDays(28),
            'mbrid' => $this->selectedMember,
            'renewal_count' => 0,
        ]);
        $this->emit('refreshBookList');
        $this->closeModal();
        /*
        $this->closeModalWithEvents([
            'bookListUpdated' => 'bookListUpdated'
        ]);
        */
    }

    public function close()
    {
        $this->closeModal();
    }
    
}
