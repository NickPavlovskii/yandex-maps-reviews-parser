<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class SpaController extends Controller
{
    public function __invoke(): Response|View
    {
        $page = public_path('spa.html');

        if (is_file($page)) {
            return response()->file($page);
        }

        return view('welcome');
    }
}
