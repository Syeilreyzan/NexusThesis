<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use Illuminate\View\View;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // Query directly from Thesis and eager-load student, supervisor, and examiner
        // $theses = Thesis::with(['student', 'supervisor', 'examiner'])
        //     ->latest()
        //     ->paginate(10);
        // $lecturers = User::role('lecturer')->orderBy('name')->get();

        return view('dashboard');
    }

    //update examiner it still need api endpoint of theses to update the exanminer
    public function updateExaminer(Request $request, Thesis $thesis)
        {
            $validated = $request->validate([
                'examiner_id' => ['nullable', 'exists:users,id'],
            ]);

            $thesis->update([
                'examiner_id' => $validated['examiner_id'],
            ]);

            return redirect()->route('admin.dashboard')
                ->with('status', 'Examiner assigned successfully!');
        }
}
