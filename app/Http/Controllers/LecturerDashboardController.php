<?php
namespace App\Http\Controllers;

use App\Models\Thesis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LecturerDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 1. Get theses where authenticated user is the Supervisor
        $svTheses = Thesis::with('student')
            ->where('supervisor_id', $userId)
            ->get();

        // 2. Get theses where authenticated user is the Examiner
        $examinerTheses = Thesis::with('student')
            ->where('examiner_id', $userId)
            ->get();

        return view('lecturer.dashboard', compact('svTheses', 'examinerTheses'));
    }
}
