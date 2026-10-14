<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ThesisController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Thesis::class);

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $theses = Thesis::latest()->get();
        } elseif ($user->hasRole('lecturer')) {
            $theses = Thesis::where(
                'supervisor_id',
                $user->id
            )->latest()->get();
        } else {
            $theses = Thesis::where(
                'student_id',
                $user->id
            )->latest()->get();
        }

        return view('theses.index', compact('theses'));
    }

    public function show(Thesis $thesis)
    {
        Gate::authorize('view', $thesis);

        return view('theses.show', compact('thesis'));
    }

    public function create()
    {
        Gate::authorize('create', Thesis::class);

        return view('theses.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Thesis::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
        ]);

        $thesis = Thesis::create([
            ...$validated,
            'student_id' => $request->user()->id,
        ]);

        return redirect()->route('theses.show', $thesis);
    }
}
