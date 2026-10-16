<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasRole('lecturer')) {
            return redirect()->route('lecturer.dashboard');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        // if ($user->hasRole('student')) {
        //     return redirect()->route('student.dashboard');
        // }

        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }

        return view('dashboard');
    }
}
