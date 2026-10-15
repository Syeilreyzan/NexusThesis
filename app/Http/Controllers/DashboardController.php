<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Jika user adalah Lecturer -> redirect ke /lecturer/dashboard
        if ($user->hasRole('lecturer')) {
            return redirect()->route('lecturer.dashboard');
        }

        // Jika user adalah Student -> redirect ke /student/dashboard
        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }

        // Jika Admin atau role lain -> tampilkan view dashboard umum
        return view('dashboard');
    }
}
