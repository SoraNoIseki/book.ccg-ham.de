<?php

namespace App\Http\Livewire\Library;

use LivewireUI\Modal\ModalComponent;
use Soranoiseki\BookGroup\Models\Library\Copy;
use Soranoiseki\BookGroup\Models\Library\Member;

class BorrowBook extends ModalComponent
{
    public $copy;

    public $book;

    public $copyId;

    public $bookId;

    public $members;

    public $selectedMember;

    public $firstname;

    public $lastname;

    public $email;

    public $telephone;

    // protected $rules = [
    //     'selectedMember' => 'required',
    // ];

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
        // $this->validate([
        //     'selectedMember' => 'required',
        // ]);
    }

    public function submit()
    {
        $this->validate([
            'selectedMember' => 'required',
        ]);
       
        $this->borrowBook($this->selectedMember);
        $this->emit('refreshBookList');
        $this->closeModal();
    }

    public function createAndSubmit()
    {
        $data = $this->validate([
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email',
            'telephone' => '',
        ]);

        $member = Member::where('first_name', trim($data['firstname']))
            ->where('last_name', trim($data['lastname']))
            ->first();
        
        if ($member) {
            $member->email = trim($data['email']);
            $member->home_phone = trim($data['telephone']);
            $member->last_change_userid = 1;
            $member->save();
        } else {
            $barcode = Member::orderBy('mbrid', 'desc')->first()->mbrid + 1000;
            $member = Member::create([
                'first_name' => trim($data['firstname']),
                'last_name' => trim($data['lastname']),
                'email' => trim($data['email']),
                'home_phone' => trim($data['telephone']),
                'barcode_nmbr' => (string)$barcode,
                'last_change_userid' => 1,
                'classification' => 2,
                'mbrshipend' => '2070-01-01',
            ]);
        }

        $this->borrowBook($member->mbrid);
        $this->emit('refreshBookList');
        $this->closeModal();
    }

    public function close()
    {
        $this->closeModal();
    }

    // TODO: move to service or model
    private function borrowBook($memberId) {
        Copy::where('bibid', $this->bookId)->where('copyid', $this->copyId)->update([
            'status_cd' => 'out',
            'status_begin_dt' => now(),
            'due_back_dt' => now()->addDays(28),
            'mbrid' => $memberId,
            'renewal_count' => 0,
        ]);
    }

    
    
}
