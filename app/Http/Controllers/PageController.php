<?php

namespace App\Http\Controllers;

use App\Content\ContentRepository;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(ContentRepository $content): View
    {
        return view('home', [
            'profile' => $content->profile(),
            'goals' => $content->goals(),
            'achievements' => $content->achievements(),
            'projects' => $content->projects(),
        ]);
    }
}
