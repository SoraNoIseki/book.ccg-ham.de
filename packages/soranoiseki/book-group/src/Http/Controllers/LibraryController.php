<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\BookGroup\Models\Library\Book;
use Soranoiseki\BookGroup\Models\Library\Member;
use Maatwebsite\Excel\Facades\Excel;
use Soranoiseki\BookGroup\Imports\ImportBooks;

class LibraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $books = Book::with(['copies' => function($query) {
            $query->orderBy('copyid', 'asc');
        }])->orderBy('bibid', 'asc')->get();
        $members = Member::all();
       
        return view('book-group::library.index', [
            'books' => $books,
            'members' => $members,
            'manageEnabled' => in_array($currentUser->id, [1, 5]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        //
    }

    public function importBooks(Request $request) 
    {
        //dd($request->file('file'));
        Excel::import(new ImportBooks, $request->file('file'));
        return Response::redirectToRoute('library.index');
    }

    public function borrowBook($bookId, $copyId)
    {
        return Response::redirectToRoute('library.index');
    }

    public function returnBook($bookId, $copyId)
    {
        return Response::redirectToRoute('library.index');
    }
}
