<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function stub(string $title)
    {
        return view('pages.stub', [
            'title' => $title.' | slots.tube',
            'heading' => $title,
            'text' => 'This page will be filled in Phase 2.',
        ]);
    }
}
