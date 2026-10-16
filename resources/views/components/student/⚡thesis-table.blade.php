<?php

use App\Models\Thesis;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
    public string $search = '';

    public ?string $previewDocumentUrl = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function theses(): LengthAwarePaginator
    {
        $query = Thesis::query()
            ->with(['student', 'supervisor', 'examiner', 'document'])
            ->select(['id', 'student_id', 'supervisor_id', 'examiner_id', 'title', 'status', 'grade', 'created_at'])
            ->where('student_id', auth()->id()) // <-- This restricts it to the auth student
            ->latest();

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

    public function viewThesis(int $thesisId): void
    {
        $thesis = Thesis::with('document')->findOrFail($thesisId);
        $this->authorize('view', $thesis);
        $this->selectedThesisId = $thesis->id;
        $this->modal('view-thesis')->show();
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

        DB::transaction(function () use ($validated) {
            $thesis = Thesis::create([
                'student_id' => auth()->id(),
                'supervisor_id' => $validated['supervisor_id'] ?: null,
                'title' => $validated['title'],
                'abstract' => $validated['abstract'],
            ]);

            $this->handleDocumentUpload($thesis);
        });

        $this->resetForm();
        $this->modal('add-thesis')->close();
        unset($this->theses);
        Flux::toast('Thesis submitted successfully.');
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

        DB::transaction(function () use ($thesis, $validated) {
            $thesis->update([
                'title' => $validated['title'],
                'abstract' => $validated['abstract'],
                'supervisor_id' => $validated['supervisor_id'] ?: null,
            ]);

            $this->handleDocumentUpload($thesis);
        });

        $this->resetForm();
        $this->modal('edit-thesis')->close();
        unset($this->theses);
        Flux::toast('Thesis updated successfully.');
    }

    /**
     * Reusable method to handle file naming, storing, and database sync.
     */
    private function handleDocumentUpload(Thesis $thesis): void
    {
        if (!$this->thesisFile) {
            return;
        }

        $userName = str_replace(' ', '_', auth()->user()->name);
        $extension = $this->thesisFile->getClientOriginalExtension();
        $fileName = $userName . '_' . time() . '.' . $extension;

        $newPath = $this->thesisFile->storeAs('thesis-documents', $fileName, 'local');

        $documentData = [
            'uploaded_by' => auth()->id(),
            'document_type' => 'thesis',
            'original_name' => $this->thesisFile->getClientOriginalName(),
            'storage_path' => $newPath,
            'mime_type' => $this->thesisFile->getMimeType(),
            'file_size' => $this->thesisFile->getSize(),
            'file_hash' => hash_file('sha256', $this->thesisFile->getRealPath()),
            'similarity_score' => null,
        ];

        if ($thesis->document) {
            $oldPath = $thesis->document->storage_path;

            $thesis->document->update($documentData);

            if ($oldPath && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        } else {
            $thesis->document()->create($documentData);
        }
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

        $thesis->update(['status' => 'pending_approval']);

        unset($this->theses);
        Flux::toast('Thesis submitted successfully.');
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

    public function resetForm(): void
    {
        $this->reset(['title', 'abstract', 'supervisor_id', 'thesisFile']);
        $this->resetValidation();
    }

    public function previewPdf()
    {
        if ($this->selectedThesis && $this->selectedThesis->document) {
            $this->previewDocumentUrl = route('theses.document.view', ['thesis' => $this->selectedThesis->slug ?? $this->selectedThesis->id]);
            Flux::modal('preview-pdf-modal')->show();
        }
    }

    // Optional: Reset the state when you close the modal
    public function resetModalState()
    {
        $this->previewDocumentUrl = null;
    }
};
?>

<div>
    @error('submitThesis')
        <flux:callout variant="danger" icon="exclamation-triangle" size="sm" class="mb-4">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    {{-- --- Action Bar --- --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="w-full max-w-sm">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search thesis title or student name..."
                clearable
            />
        </div>

        @if(auth()->user()->hasRole('student'))
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('add-thesis').show()">
                Add Thesis
            </flux:button>
        @endif
    </div>

    {{-- --- Main Table --- --}}
    <flux:table :paginate="$this->theses">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Thesis Title</flux:table.column>
            <flux:table.column>Document</flux:table.column>
            <flux:table.column>Supervisor</flux:table.column>
            <flux:table.column>Examiner</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Grade</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->theses as $thesis)
                <flux:table.row :key="$thesis->id">
                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->theses->currentPage() - 1) * $this->theses->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="text-sm text-zinc-900 dark:text-white">
                            {{ $thesis->student->name ?? 'Unknown Student' }}
                        </div>
                        <div class="font-medium text-zinc-500">{{ $thesis->title }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($thesis->document)
                            <flux:badge color="green" icon="check-circle">Uploaded</flux:badge>
                        @else
                            <flux:badge color="red">Not Uploaded</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        {{ $thesis->supervisor->name ?? 'Not assigned' }}
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        {{ $thesis->examiner->name ?? 'Not assigned' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm"
                            color="{{ match($thesis->status) {
                                'draft' => 'zinc',
                                'pending_approval' => 'amber',
                                'completed', 'approved' => 'green',
                                'rejected' => 'red',
                                default => 'zinc',
                            } }}">
                            {{ str($thesis->status)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        @if($thesis->grade)
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $thesis->grade }}</span>
                        @else
                            <span class="text-zinc-400 italic text-sm">Not Graded</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $thesis->created_at->format('M j, Y') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" icon="eye" wire:click="viewThesis({{ $thesis->id }})">
                                View
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-6">
                        No thesis records found matching your search.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- --- Modals --- --}}

    {{-- View Modal --}}
    {{-- 1. Original Thesis Details Modal --}}
    <flux:modal name="view-thesis" class="md:w-[600px]">
        @if ($this->selectedThesis)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Thesis Details</flux:heading>
                    <flux:text class="mt-2">View the full details of this thesis.</flux:text>
                </div>

                <div>
                    <flux:heading size="sm">Title</flux:heading>
                    <p class="mt-1">{{ $this->selectedThesis->title }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Abstract</flux:heading>
                    <p class="mt-1 whitespace-pre-line">{{ $this->selectedThesis->abstract ?: 'No abstract provided.' }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Supervisor (Lecturer)</flux:heading>
                    <p class="mt-1">{{ $this->selectedThesis->supervisor?->name ?? 'Not assigned' }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Examiner</flux:heading>
                    <p class="mt-1">{{ $this->selectedThesis->examiner?->name ?? 'Not assigned' }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Thesis Document</flux:heading>

                    @if ($this->selectedThesis->document)
                        <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex min-w-0 items-center gap-3">
                                <flux:icon.document-text class="size-8 shrink-0 text-zinc-500" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $this->selectedThesis->document->original_name }}</p>
                                    <p class="text-xs text-zinc-500">{{ number_format($this->selectedThesis->document->file_size / 1024, 1) }} KB</p>
                                </div>
                            </div>

                            {{-- Opens the new PDF Modal --}}
                            <flux:button size="sm" variant="primary" icon="eye" wire:click="previewPdf" wire:loading.attr="disabled">
                                View
                            </flux:button>
                        </div>
                    @else
                        <p class="mt-1 text-sm text-zinc-500">No thesis document uploaded.</p>
                    @endif
                </div>

                <div>
                    <flux:heading size="sm">Status</flux:heading>
                    <flux:badge class="mt-1">{{ str($this->selectedThesis->status)->replace('_', ' ')->title() }}</flux:badge>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Close</flux:button>
                    </flux:modal.close>

                    @if ($this->selectedThesis->student_id === auth()->id() && $this->selectedThesis->status === 'draft')
                        <flux:button type="button" variant="filled" color="amber" icon="pencil" wire:click="editThesis({{ $this->selectedThesis->id }})">
                            Edit
                        </flux:button>

                        <flux:button type="button" variant="primary" icon="arrow-up-circle" wire:click="submitThesis({{ $this->selectedThesis->id }})" wire:confirm="Are you sure you want to submit this thesis?">
                            Submit Thesis
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- 2. New Dedicated PDF Preview Modal --}}
    <flux:modal name="preview-pdf-modal" class="w-full max-w-5xl h-[85vh]">
        <div class="flex flex-col h-full space-y-4">
            <div class="shrink-0 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Document Preview</flux:heading>
                </div>
            </div>

            <div class="flex-1 min-h-0 bg-zinc-100 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden relative">
                <div wire:loading wire:target="previewPdf" class="absolute inset-0 flex items-center justify-center bg-zinc-100/80 dark:bg-zinc-800/80 z-10">
                    Loading document...
                </div>

                @if($previewDocumentUrl)
                    <iframe
                        src="{{ $previewDocumentUrl }}"
                        class="w-full h-full border-0"
                        title="Thesis Document Preview"
                    ></iframe>
                @endif
            </div>

            <div class="shrink-0 flex justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Close Preview</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal name="edit-thesis" class="md:w-[600px]">
        <form wire:submit="updateThesis" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Thesis</flux:heading>
                <flux:text class="mt-2">Update your thesis details and document.</flux:text>
            </div>

            <div>
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error class="mt-0!" name="title" />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Abstract</flux:label>
                    <flux:textarea wire:model="abstract" rows="5" />
                    <flux:error class="mt-0!" name="abstract" />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Supervisor</flux:label>
                    <flux:select wire:model="supervisor_id" placeholder="Select a lecturer">
                        <flux:select.option value="">No supervisor selected</flux:select.option>
                        @foreach ($this->lecturers as $lecturer)
                            <flux:select.option value="{{ $lecturer->id }}">{{ $lecturer->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error class="mt-0!" name="supervisor_id" />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Replace Thesis Document (optional)</flux:label>

                    @if ($this->selectedThesis?->document)
                        <div class="mb-3 flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50 p-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:icon.document-text class="size-5 text-zinc-400" />
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Current file:</span>
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $this->selectedThesis->document->original_name }}</span>
                        </div>
                    @endif

                    <input type="file" wire:model="thesisFile" accept=".pdf,.doc,.docx" class="block w-full min-w-0 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
                    <flux:error class="mt-0!" name="thesisFile" />

                    <div wire:loading wire:target="thesisFile" class="text-sm mt-1">Uploading file...</div>

                    @if ($thesisFile)
                        <p class="mt-2 text-sm text-zinc-500">New file: {{ $thesisFile->getClientOriginalName() }}</p>
                    @endif

                    <p class="mt-1 text-xs text-zinc-500">Accepted formats: PDF, DOC, DOCX. Maximum size: 20 MB.</p>
                </flux:field>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateThesis">Save Changes</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Add Modal --}}
    <flux:modal name="add-thesis" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Submit New Thesis</flux:heading>
                <flux:text class="mt-2">Enter your thesis title, abstract, and initial document.</flux:text>
            </div>

            <form wire:submit="saveThesis" class="space-y-4">
                <flux:field>
                    <flux:label>Thesis Title</flux:label>
                    <flux:input wire:model="title" placeholder="Enter thesis title" />
                    <flux:error class="mt-0!" name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Abstract</flux:label>
                    <flux:textarea wire:model="abstract" placeholder="Enter thesis abstract" rows="5" />
                    <flux:error class="mt-0!" name="abstract" />
                </flux:field>

                <flux:field>
                    <flux:label>Choose Supervisor</flux:label>
                    <flux:select wire:model="supervisor_id" placeholder="Select a lecturer">
                        <flux:select.option value="">No supervisor selected</flux:select.option>
                        @foreach ($this->lecturers as $lecturer)
                            <flux:select.option value="{{ $lecturer->id }}">{{ $lecturer->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error class="mt-0!" name="supervisor_id" />
                </flux:field>

                <div>
                    <flux:label for="thesisFile">Thesis document (PDF, DOC, DOCX)</flux:label>
                    <div class="mt-2 min-w-0">
                        <input id="thesisFile" type="file" wire:model="thesisFile" accept=".pdf,.doc,.docx" class="block w-full min-w-0 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
                    </div>

                    <div wire:loading wire:target="thesisFile" class="mt-2 text-sm text-zinc-500">Uploading file...</div>

                    @if ($thesisFile)
                        <p class="mt-2 truncate text-sm text-green-600">Selected: {{ $thesisFile->getClientOriginalName() }}</p>
                    @endif

                    <flux:error class="mt-0!" name="thesisFile" />
                    <p class="mt-1 text-xs text-zinc-500">Accepted formats: PDF, DOC, DOCX. Maximum size: 20 MB.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="resetForm">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveThesis">Create Thesis</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
