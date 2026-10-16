<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThesisController extends Controller
{
    public function index(): View
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

    public function show(Thesis $thesis): View
    {
        Gate::authorize('view', $thesis);

        return view('theses.show', compact('thesis'));
    }

    public function create(): View
    {
        Gate::authorize('create', Thesis::class);

        return view('theses.create');
    }

    public function store(Request $request): RedirectResponse
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

    // public function viewDocument(Thesis $thesis)
    // {
    //     Gate::authorize('view', $thesis);

    //     $document = $thesis->document;

    //     abort_unless($document, 404);

    //     $disk = Storage::disk('local');

    //     abort_unless($disk->exists($document->storage_path), 404);

    //     return $disk->response(
    //         $document->storage_path,
    //         basename($document->original_name),
    //         [
    //             'Content-Type' => $document->mime_type,
    //             'Content-Disposition' => 'inline',
    //             'X-Content-Type-Options' => 'nosniff',
    //         ]
    //     );
    // }
    public function viewDocument(Thesis $thesis): StreamedResponse
    {
        Gate::authorize('view', $thesis);

        $document = $thesis->document;

        abort_if($document === null, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($document->storage_path), 404);

        return $disk->response(
            $document->storage_path,
            basename($document->original_name),
            [
                'Content-Type' => $document->mime_type,
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
