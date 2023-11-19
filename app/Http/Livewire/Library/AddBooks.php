<?php

namespace App\Http\Livewire\Library;

use Livewire\Component;
use Livewire\WithPagination;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Cpoy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Soranoiseki\BookGroup\Models\Library\BookDatabase;

class AddBooks extends Component
{
    public $externalMode = true;

    public $isbn;

    public $title;

    public $author;

    public $publishing;

    public $category;

    public $code;

    public $quantity = 1;

    public $apiStatus = -1;

    // protected $listeners = ['refreshBookList' => '$refresh'];

    
    public function render()
    {
        return view('livewire.library.add-books', [
            
        ]);
    }

    public function create() {
        $isbn = trim($this->isbn);
        $code = trim($this->code);
        $title = trim($this->title);
        $author = trim($this->author);
        $publishing =trim($this->publishing);
        $category = trim($this->category);
        $quantity = $this->quantity;
        $existCopies = 0;
        
        try {
            // update book data
            $book = Book::where('call_nmbr2', $isbn)->firstOrFail();
            $book->fill([
                'title' => $title,
                'responsibility_stmt' => $publishing,
                'author' => $author,
                'topic1' => $category,
                // 'last_change_dt' => now(),
                'last_change_userid' => 1,
            ])->save();
            $existCopies = $book->copies->count();
        } catch (ModelNotFoundException $e) {
            if ($code === '') {
                $lastBook = Book::where('call_nmbr1', 'like', 'FMCD%')
                    ->orderBy('call_nmbr1', 'desc')
                    ->first();
                $lastId = $lastBook ? (int)str_replace('FMCD', '', $lastBook->call_nmbr1) : 0; 
                $code = 'FMCD' . str_pad((string)($lastId + 1), 4, '0', STR_PAD_LEFT);
            }

            $book = Book::create([
                'create_dt' => now(),
                // 'last_change_dt' => now(),
                'last_change_userid' => 1,
                'material_cd' => 2,
                'collection_cd' => 2,
                'call_nmbr1' => $code,
                'call_nmbr2' => $isbn,
                'call_nmbr3' => '',
                'title' => $title,
                'title_remainder' => '',
                'responsibility_stmt' => $publishing,
                'author' => $author,
                'topic1' => $category,
                'topic2' => '',
                'topic3' => '',
                'topic4' => '',
                'topic5' => '',
                'opac_flg' => 'Y',
            ]);
        }

        // create book & copies
        for($i = $existCopies + 1; $i <= (int)$quantity; $i++) {
            $copy = $code . '.' . $i;
            $book->copies()->create([
                'bibid' => $book->bibid,
                'create_dt' => now(),
                'copy_desc' => $copy,
                'barcode_nmbr' => '0',
                'status_cd' => 'in',
                'status_begin_dt' => now(),
                'due_back_dt' => null,
                'mbrid' => null,
                'renewal_count' => '0',
            ]);
        }
       
        $this->resetForm();
        $this->emit('refreshBookList');
    }

    public function onInputISBN($isbn) {
        $apiKey = env('ISBN_API_KEY', '');
        if ($apiKey !== '' && (strlen($isbn) === 13 || strlen($isbn) === 10)) {
            $this->category = $isbn;
            $bookDatabase = BookDatabase::where(["isbn" => $isbn])->first();
            if ($bookDatabase) {
                // read from database
                $this->apiStatus = -1;
                $data = json_decode($bookDatabase->data, true);

                if (isset($data['title'])) {
                    if (isset($data['subtitle'])) {
                        $this->title = $data['title'] . ' - ' . $data['subtitle'];
                    } else {
                        $this->title = $data['title'];
                    }
                }
                if (isset($data['authors'])) {
                    $this->author = implode(', ', $data['authors']);
                }
                if (isset($data['publishing'])) {
                    $this->publishing = $data['publishing'];
                }
            } else {
                $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . $isbn;
                $response = Http::get($url);

                if ($response["totalItems"] == 0) {
                    // no results
                    $this->apiStatus = 0;
                } else {
                    $this->apiStatus = 1;
                    $data = $response['items'][0]['volumeInfo'];
                    
                    // save to database
                    BookDatabase::create([
                        "isbn" => $isbn,
                        "data" => json_encode($data)
                    ]);

                    if (isset($data['title'])) {
                        if (isset($data['subtitle'])) {
                            $this->title = $data['title'] . ' - ' . $data['subtitle'];
                        } else {
                            $this->title = $data['title'];
                        }
                    }
                    if (isset($data['authors'])) {
                        $this->author = implode(', ', $data['authors']);
                    }
                    if (isset($data['publishing'])) {
                        $this->publishing = $data['publishing'];
                    }
                }
            }
        }
    }

    public function resetForm() {
        $this->isbn = '';
        $this->title = '';
        $this->author = '';
        $this->publishing = '';
        $this->code = '';
        $this->apiStatus = -1;
    }
    
    
}
