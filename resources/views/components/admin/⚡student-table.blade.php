<?php

use App\Models\Thesis;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use App\Models\User;

new class extends Component {
    use WithPagination, WithFileUploads;

    public string $title = '';
    public string $abstract = '';
    public $thesisFile = null;
    public ?int $supervisor_id = null;
    public ?int $selectedThesisId = null;
    public bool $showThesisModal = false;
    public string $removalReason = '';

    // --- Added Search Property ---
    public string $search = '';

    // --- Reset pagination when search changes ---
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function viewThesis(int $thesisId): void
    {
        $thesis = Thesis::with('document')->findOrFail($thesisId);

        $this->authorize('view', $thesis);

        $this->selectedThesisId = $thesis->id;

        $this->modal('view-thesis')->show();
    }

    public function closeThesisModal(): void
    {
        $this->showThesisModal = false;
        $this->selectedThesisId = null;
    }

    #[Computed]
    public function theses(): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = Thesis::query()
            // --- Added 'student' to eager loading ---
            ->with(['student', 'supervisor', 'examiner', 'document'])
            ->select(['id', 'student_id', 'supervisor_id', 'examiner_id', 'title', 'status', 'created_at'])
            ->latest();

        if ($user->hasRole('lecturer')) {
            // Lecturers see theses assigned to them as supervisor or examiner.
            $query->where(function ($q) use ($user) {
                $q->where('supervisor_id', $user->id)->orWhere('examiner_id', $user->id);
            });
        } elseif ($user->hasRole('student')) {
            // Students see only their own theses.
            $query->where('student_id', $user->id);
        } elseif ($user->hasRole('admin')) {
            // Admins see all theses.
            // No additional filtering needed.
            $query->whereNotNull('id'); // This is just a placeholder to ensure the query is valid.
        } else {
            abort(403);
        }

        // --- Apply Search Filter (Title & Student Name) ---
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhereHas('student', function ($subQuery) {
                      $subQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->paginate(10);
    }

    public function saveThesis(): void
    {
        abort_unless(auth()->user()->hasRole('student'), 403);
        $this->authorize('create', Thesis::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'thesisFile' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ]);

        if ($validated['supervisor_id'] && !User::role('lecturer')->whereKey($validated['supervisor_id'])->exists()) {
            $this->addError('supervisor_id', 'Please select a valid lecturer.');
            return;
        }

        $path = null;
        $thesis = null;

        try {
            if ($this->thesisFile) {
                $path = $this->thesisFile->store('thesis-documents', 'local');
            }

            $thesis = Thesis::create([
                'student_id' => auth()->id(),
                'supervisor_id' => $validated['supervisor_id'] ?: null,
                'title' => $validated['title'],
                'abstract' => $validated['abstract'],
            ]);

            if ($this->thesisFile && $path) {
                $thesis->document()->create([
                    'uploaded_by' => auth()->id(),
                    'document_type' => 'thesis',
                    'original_name' => $this->thesisFile->getClientOriginalName(),
                    'storage_path' => $path,
                    'mime_type' => $this->thesisFile->getMimeType(),
                    'file_size' => $this->thesisFile->getSize(),
                    'file_hash' => hash_file('sha256', $this->thesisFile->getRealPath()),
                    'similarity_score' => null,
                ]);
            }
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            if ($thesis) {
                $thesis->delete();
            }
            throw $e;
        }

        $this->resetForm();
        $this->modal('add-thesis')->close();
        unset($this->theses);
        Flux::toast('Thesis submitted successfully.');
    }

    public function editThesis(int $thesisId): void
    {
        $thesis = Thesis::findOrFail($thesisId);
        $this->authorize('view', $thesis);
        abort_unless($thesis->student_id === auth()->id(), 403);

        $this->selectedThesisId = $thesis->id;
        $this->title = $thesis->title;
        $this->abstract = $thesis->abstract ?? '';
        $this->supervisor_id = $thesis->supervisor_id;
        $this->thesisFile = null;

        $this->resetValidation();
        $this->modal('view-thesis')->close();
        $this->modal('edit-thesis')->show();
    }

    public function updateThesis(): void
    {
        $thesis = Thesis::findOrFail($this->selectedThesisId);
        Gate::authorize('view', $thesis);
        abort_unless($thesis->student_id === auth()->id(), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'thesisFile' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ]);

        if ($validated['supervisor_id'] && !User::role('lecturer')->whereKey($validated['supervisor_id'])->exists()) {
            $this->addError('supervisor_id', 'Please select a valid lecturer.');
            return;
        }

        $newPath = null;

        try {
            if ($this->thesisFile) {
                $newPath = $this->thesisFile->store('thesis-documents', 'local');
            }

            $thesis->update([
                'title' => $validated['title'],
                'abstract' => $validated['abstract'],
                'supervisor_id' => $validated['supervisor_id'] ?: null,
            ]);

            if ($this->thesisFile && $newPath) {
                $document = $thesis->document;

                if ($document) {
                    $oldPath = $document->storage_path;

                    $document->update([
                        'uploaded_by' => auth()->id(),
                        'document_type' => 'thesis',
                        'original_name' => $this->thesisFile->getClientOriginalName(),
                        'storage_path' => $newPath,
                        'mime_type' => $this->thesisFile->getMimeType(),
                        'file_size' => $this->thesisFile->getSize(),
                        'file_hash' => hash_file('sha256', $this->thesisFile->getRealPath()),
                    ]);

                    if ($oldPath) {
                        Storage::disk('local')->delete($oldPath);
                    }
                } else {
                    $thesis->document()->create([
                        'uploaded_by' => auth()->id(),
                        'document_type' => 'thesis',
                        'original_name' => $this->thesisFile->getClientOriginalName(),
                        'storage_path' => $newPath,
                        'mime_type' => $this->thesisFile->getMimeType(),
                        'file_size' => $this->thesisFile->getSize(),
                        'file_hash' => hash_file('sha256', $this->thesisFile->getRealPath()),
                        'similarity_score' => null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $e;
        }

        $this->reset(['title', 'abstract', 'supervisor_id', 'thesisFile']);
        $this->resetValidation();
        $this->modal('edit-thesis')->close();
        unset($this->theses);
        \Flux\Flux::toast('Thesis updated successfully.');
    }

    #[Computed]
    public function lecturers()
    {
        return User::role('lecturer')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function selectedThesis()
    {
        if (!$this->selectedThesisId) {
            return null;
        }
        return Thesis::with(['document', 'supervisor', 'examiner'])->find($this->selectedThesisId);
    }

    public function submitThesis(int $thesisId): void
    {
        $thesis = Thesis::with('document')->findOrFail($thesisId);
        $this->authorize('view', $thesis);
        abort_unless($thesis->student_id === auth()->id(), 403);

        if (!$thesis->supervisor_id) {
            $this->addError('submitThesis', 'Please select a lecturer before submitting your thesis.');
            return;
        }

        if (!$thesis->document) {
            $this->addError('submitThesis', 'Please upload your thesis document before submitting.');
            return;
        }

        $thesis->update([
            'status' => 'submitted',
        ]);

        unset($this->theses);
        Flux::toast('Thesis submitted successfully.');
    }

    public function removeThesis(): void
    {
        $validated = $this->validate([
            'selectedThesisId' => ['required', 'integer', 'exists:theses,id'],
            'removalReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $thesis = Thesis::with('document')->findOrFail($validated['selectedThesisId']);
        $this->authorize('delete', $thesis);

        DB::transaction(function () use ($thesis, $validated) {
            if ($thesis->document) {
                $thesis->document->delete();
            }
            $thesis->update([
                'removal_reason' => trim($validated['removalReason']),
            ]);
            $thesis->delete();
        });

        Flux::modal('remove-thesis')->close();
        $this->reset(['selectedThesisId', 'removalReason']);
        $this->resetValidation();
        unset($this->theses);
        session()->flash('success', 'Thesis removed successfully.');
    }

    public function confirmRemoveThesis(int $thesisId): void
    {
        $thesis = Thesis::findOrFail($thesisId);
        $this->authorize('delete', $thesis);

        $this->selectedThesisId = $thesis->id;
        $this->removalReason = '';
        $this->resetValidation();
        Flux::modal('remove-thesis')->show();
    }

    public function resetForm(): void
    {
        $this->reset(['title', 'abstract', 'supervisor_id', 'thesisFile']);
        $this->resetValidation();
    }
};
?>

<div>
    @error('submitThesis')
        <flux:callout variant="danger" icon="exclamation-triangle" size="sm" class="mb-4">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    {{-- --- Added Search Bar --- --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="w-full max-w-sm">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search thesis title or student name..."
                clearable
            />
        </div>
    </div>

    <flux:table :paginate="$this->theses">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column> {{-- --- Added Number Column --- --}}
            <flux:table.column>Thesis Title</flux:table.column>
            <flux:table.column>Document</flux:table.column>
            <flux:table.column>Supervisor</flux:table.column>
            <flux:table.column>Examiner</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->theses as $thesis)
                <flux:table.row :key="$thesis->id">
                    {{-- --- Added Row Numbering --- --}}
                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->theses->currentPage() - 1) * $this->theses->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    {{-- --- Added Student Name Display --- --}}
                    <flux:table.cell>
                        <div class="text-sm text-zinc-900 dark:text-white">
                            {{ $thesis->student->name ?? 'Unknown Student' }}
                        </div>
                        <div class="font-medium text-zinc-500">{{ $thesis->title }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($thesis->document)
                            <flux:badge color="green" icon="check-circle">
                                Uploaded
                            </flux:badge>
                        @else
                            <flux:badge color="red">
                                Not Uploaded
                            </flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell variant="strong">{{ $thesis->supervisor->name ?? 'Not assigned' }}
                    </flux:table.cell>
                    <flux:table.cell variant="strong">{{ $thesis->examiner->name ?? 'Not assigned' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm"
                            color="{{ match($thesis->status) {
                                'draft' => 'zinc',
                                'submitted', 'pending_review' => 'amber',
                                'approved' => 'green',
                                'rejected' => 'red',
                                default => 'zinc',
                            } }}">
                            {{ str($thesis->status)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $thesis->created_at->format('M j, Y') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            {{-- View Thesis --}}
                            <flux:button size="sm" variant="primary" icon="eye"
                                wire:click="viewThesis({{ $thesis->id }})">
                                View
                            </flux:button>

                            {{-- Edit Thesis --}}
                            @if ($thesis->status === 'draft' && $thesis->student_id === auth()->id())
                                <flux:button size="sm" variant="filled" color="amber" icon="pencil"
                                    wire:click="editThesis({{ $thesis->id }})">
                                    Edit
                                </flux:button>
                            @endif

                            {{-- Submit Thesis --}}
                            @if (auth()->user()->hasRole('student') && $thesis->status === 'draft')
                                <flux:button size="sm" variant="primary" icon="arrow-up-circle"
                                    wire:click="submitThesis({{ $thesis->id }})"
                                    wire:confirm="Are you sure you want to submit this thesis?">
                                    Submit
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    {{-- --- Updated Colspan to 8 --- --}}
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-6">
                        No thesis records found matching your search.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- MODALS BELOW THIS LINE (No changes needed) --}}

    <flux:modal name="view-thesis" class="md:w-[600px]">
        @if ($selectedThesisId)
            @php
                $selectedThesis = \App\Models\Thesis::find($selectedThesisId);
            @endphp

            @if ($selectedThesis)
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Thesis Details</flux:heading>
                        <flux:text class="mt-2">
                            View the full details of this thesis.
                        </flux:text>
                    </div>

                    {{-- Thesis Title --}}
                    <div>
                        <flux:heading size="sm">Title</flux:heading>
                        <p class="mt-1">{{ $selectedThesis->title }}</p>
                    </div>

                    {{-- Abstract --}}
                    <div>
                        <flux:heading size="sm">Abstract</flux:heading>
                        <p class="mt-1 whitespace-pre-line">
                            {{ $selectedThesis->abstract ?: 'No abstract provided.' }}
                        </p>
                    </div>

                    {{-- Supervisor / Lecturer --}}
                    <div>
                        <flux:heading size="sm">Supervisor (Lecturer)</flux:heading>
                        <p class="mt-1">
                            {{ $selectedThesis->supervisor?->name ?? 'Not assigned' }}
                        </p>
                    </div>

                    {{-- Examiner --}}
                    <div>
                        <flux:heading size="sm">Examiner</flux:heading>
                        <p class="mt-1">
                            {{ $selectedThesis->examiner?->name ?? 'Not assigned' }}
                        </p>
                    </div>

                    {{-- Thesis Document --}}
                    <div>
                        <flux:heading size="sm">Thesis Document</flux:heading>

                        @if ($selectedThesis->document)
                            <div
                                class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="flex min-w-0 items-center gap-3">
                                    <flux:icon.document-text class="size-8 shrink-0 text-zinc-500" />

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">
                                            {{ $selectedThesis->document->original_name }}
                                        </p>

                                        <p class="text-xs text-zinc-500">
                                            {{ number_format($selectedThesis->document->file_size / 1024, 1) }} KB
                                        </p>
                                    </div>
                                </div>

                                <flux:button size="sm" variant="primary" icon="eye"
                                    :href="route('theses.document.view', ['thesis' => $selectedThesis->slug])"
                                    target="_blank">
                                    View
                                </flux:button>
                            </div>
                        @else
                            <p class="mt-1 text-sm text-zinc-500">
                                No thesis document uploaded.
                            </p>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div>
                        <flux:heading size="sm">Status</flux:heading>
                        <flux:badge class="mt-1">
                            {{ str($selectedThesis->status)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </div>

                    {{-- Submission Date --}}
                    <div>
                        <flux:heading size="sm">Submitted</flux:heading>
                        <p class="mt-1">
                            {{ $selectedThesis->created_at?->format('M j, Y') ?? '—' }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">
                                Close
                            </flux:button>
                        </flux:modal.close>

                        @if ($selectedThesis->student_id === auth()->id())
                            <flux:button type="button" variant="primary" icon="pencil"
                                wire:click="editThesis({{ $selectedThesis->id }})">
                                Edit Thesis
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>

    <flux:modal name="edit-thesis" class="md:w-[600px]">
        <form wire:submit="updateThesis" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Thesis</flux:heading>
                <flux:text class="mt-2">
                    Update your thesis details and document.
                </flux:text>
            </div>

            <div>
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error  class="mt-0!" name="title" />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Abstract</flux:label>
                    <flux:textarea wire:model="abstract" rows="5" />
                    <flux:error  class="mt-0!" name="abstract" />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Supervisor</flux:label>

                    <flux:select wire:model="supervisor_id" placeholder="Select a lecturer">
                        <flux:select.option value="">
                            No supervisor selected
                        </flux:select.option>

                        @foreach ($this->lecturers as $lecturer)
                            <flux:select.option value="{{ $lecturer->id }}">
                                {{ $lecturer->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:error  class="mt-0!" name="supervisor_id" />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Replace Thesis Document (optional)</flux:label>

                    <input type="file" wire:model="thesisFile" accept=".pdf,.doc,.docx"
                        class="block w-full text-sm" />

                    <flux:error  class="mt-0!" name="thesisFile" />

                    @if ($thesisFile)
                        <p class="mt-2 text-sm text-zinc-500">
                            New file: {{ $thesisFile->getClientOriginalName() }}
                        </p>
                    @endif

                    <div wire:loading wire:target="thesisFile" class="text-sm">
                        Uploading file...
                    </div>
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                    wire:target="updateThesis">
                    Save Changes
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
