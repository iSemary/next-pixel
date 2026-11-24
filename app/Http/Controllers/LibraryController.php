<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    /**
     * Display the library page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Library');
    }
}
