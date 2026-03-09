<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(){
        return  Books::all();
    }

    public function show($id){
        return Books::find($id);
    }

    public function showByCategorie($category){
        
    }
}
