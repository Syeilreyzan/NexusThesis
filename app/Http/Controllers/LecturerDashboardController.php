<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LecturerDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }
}
