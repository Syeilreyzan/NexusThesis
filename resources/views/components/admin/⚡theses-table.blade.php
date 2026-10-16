<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Thesis;
use App\Models\User;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public ?int $selectedThesisId = null;
    public ?int $selectedExaminerId = null; // Added for the examiner dropdown
    public ?string $previewDocumentUrl = null;

    public string $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function theses(): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = Thesis::query()
            ->with(['supervisor', 'examiner', 'document', 'student'])
            ->where('status', '!=', 'draft')
            ->select(['id', 'student_id', 'supervisor_id', 'examiner_id', 'title', 'status', 'created_at'])
            ->latest();

        if (!empty($this->search)) {
            $query->where('title', 'like', '%' . $this->search . '%');
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

    // New Computed Property: Fetches lecturers BUT hides the assigned supervisor
    #[Computed]
    public function availableExaminers()
    {
        if (!$this->selectedThesisId) {
            return collect();
        }

        $thesis = Thesis::find($this->selectedThesisId);
        $supervisorId = $thesis ? $thesis->supervisor_id : null;

        $query = User::role('lecturer')->orderBy('name');

        // Hide the supervisor from the dropdown list
        if ($supervisorId) {
            $query->where('id', '!=', $supervisorId);
        }

        return $query->get(['id', 'name']);
    }

    #[Computed]
    public function selectedThesis()
    {
        if (!$this->selectedThesisId) {
            return null;
        }

        return Thesis::with(['document', 'supervisor', 'examiner'])->find($this->selectedThesisId);
    }

    // --- Action Methods ---

    public function previewThesis(int $id): void
    {
        $thesis = Thesis::with('document')->findOrFail($id);

        $this->selectedThesisId = $thesis->id;

        if ($thesis->document) {
            $this->previewDocumentUrl = route('theses.document.view', ['thesis' => $thesis->slug ?? $thesis->id]);
        } else {
            $this->previewDocumentUrl = null;
        }

        Flux::modal('preview-thesis')->show();
    }

    // Open Assign Examiner Modal
    public function openAssignExaminerModal(int $id)
    {
        $thesis = Thesis::findOrFail($id);
        $this->selectedThesisId = $thesis->id;
        $this->selectedExaminerId = $thesis->examiner_id; // Pre-fill if already assigned

        $this->resetValidation();
        Flux::modal('assign-examiner')->show();
    }

    // Save the assigned Examiner
    public function assignExaminer()
    {
        $this->validate([
            'selectedExaminerId' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($this->selectedThesisId) {
            $thesis = Thesis::findOrFail($this->selectedThesisId);

            // Double check security: Ensure examiner isn't the supervisor
            if ($thesis->supervisor_id === (int) $this->selectedExaminerId) {
                $this->addError('selectedExaminerId', 'The examiner cannot be the same as the supervisor.');
                return;
            }

            $thesis->update([
                'examiner_id' => $this->selectedExaminerId,
            ]);

            Flux::modal('assign-examiner')->close();
            $this->selectedThesisId = null;
            $this->selectedExaminerId = null;

            Flux::toast('Examiner assigned successfully.');
        }
    }

    public function editThesis($id)
    {
        return redirect()->route('theses.edit', $id);
    }

    public function submitThesis($id)
    {
        $thesis = Thesis::findOrFail($id);

        if ($thesis->student_id === auth()->id() && $thesis->status === 'draft') {
            $thesis->update(['status' => 'submitted']);
            session()->flash('success', 'Thesis submitted successfully.');
        }
    }

    public function deleteThesis()
    {
        if ($this->selectedThesisId) {
            $thesis = Thesis::findOrFail($this->selectedThesisId);
            $thesis->delete();

            $this->selectedThesisId = null;
            session()->flash('success', 'Thesis removed.');
        }
    }
};
?>

<div>
    {{-- Success Message --}}
    @if (session()->has('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search Bar --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="w-full max-w-sm">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search thesis by title..."
                clearable
            />
        </div>
    </div>

    <flux:table :paginate="$this->theses">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Thesis Title</flux:table.column>
            <flux:table.column>Document</flux:table.column>
            <flux:table.column>Supervisor</flux:table.column>
            <flux:table.column>Examiner</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column class="flex justify-center">Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->theses as $thesis)
                <flux:table.row :key="$thesis->id">

                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->theses->currentPage() - 1) * $this->theses->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $thesis->title }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $thesis->student->name ?? 'Unknown Student' }}
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($thesis->document)
                            <flux:badge color="green" icon="check-circle">Uploaded</flux:badge>
                        @else
                            <flux:badge color="red">Not Uploaded</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">{{ $thesis->supervisor->name ?? 'Not assigned' }}</flux:table.cell>

                    <flux:table.cell variant="strong">{{ $thesis->examiner->name ?? 'Not assigned' }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm"
                            color="{{ match($thesis->status) {
                                    'draft' => 'zinc',
                                    'submitted', 'pending_approval', 'approved' => 'amber',
                                    'completed' => 'green',
                                    'rejected' => 'red',
                                    default => 'zinc',
                                } }}">
                            {{ match($thesis->status) {
                                'pending_approval' => 'Result Awaited',
                                'approved' => empty($thesis->examiner_id) ? 'Pending Assignment' : 'Approved',
                                default => str($thesis->status)->replace('_', ' ')->title()
                            } }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $thesis->created_at->format('M j, Y') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex justify-center gap-2">
                            {{-- Preview Thesis PDF in Modal --}}
                            <flux:button size="sm" variant="primary" icon="document-text"
                                wire:click="previewThesis({{ $thesis->id }})">
                                Preview
                            </flux:button>

                            @if (!$thesis->examiner)
                                <flux:button size="sm" variant="filled" color="indigo" icon="user-plus"
                                    wire:click="openAssignExaminerModal({{ $thesis->id }})">
                                    Assign
                                </flux:button>
                            @endif

                            {{-- Remove Thesis --}}
                            <flux:button size="sm" variant="danger" icon="trash"
                                wire:click="$set('selectedThesisId', {{ $thesis->id }})"
                                x-on:click="$flux.modal('remove-thesis').show()">
                                Remove
                            </flux:button>
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-6">
                        No thesis records found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- --- Assign Examiner Modal --- --}}
    <flux:modal name="assign-examiner" class="md:w-[450px]">
        <form wire:submit="assignExaminer" class="space-y-6">
            <div>
                <flux:heading size="lg">Assign Examiner</flux:heading>
            </div>

            <flux:field>
                <flux:label>Examiner</flux:label>
                <flux:select wire:model="selectedExaminerId" placeholder="Select an examiner">
                    <flux:select.option value="">Choose an examiner...</flux:select.option>

                    {{-- Uses the new computed property that filters out the supervisor --}}
                    @foreach ($this->availableExaminers as $lecturer)
                        <flux:select.option value="{{ $lecturer->id }}">
                            {{ $lecturer->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error class="mt-0!" name="selectedExaminerId" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="assignExaminer">
                    Save Assignment
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Remove Thesis Modal --}}
    <flux:modal name="remove-thesis" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Thesis?</flux:heading>
                <flux:subheading>
                    Are you sure you want to remove this thesis? This action can be undone later by an administrator.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="deleteThesis" x-on:click="$flux.modal('remove-thesis').close()">
                    Remove
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- PDF Preview Modal --}}
    <flux:modal name="preview-thesis" class="w-full max-w-5xl h-[85vh]">
        <div class="flex flex-col h-full space-y-4">
            <div class="shrink-0">
                <flux:heading size="lg">Review Thesis Document</flux:heading>
            </div>

            <div class="flex-1 min-h-0 bg-zinc-100 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden relative">
                <div wire:loading wire:target="previewThesis" class="absolute inset-0 flex items-center justify-center bg-zinc-100/80 dark:bg-zinc-800/80 z-10">
                    Loading document...
                </div>

                @if($previewDocumentUrl)
                    <iframe
                        src="{{ $previewDocumentUrl }}"
                        class="w-full h-full border-0"
                        title="Uploaded PDF Preview"
                    ></iframe>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-zinc-500">
                        <flux:icon.document-minus class="size-12 mb-2 opacity-50" />
                        <p>No document was uploaded for this thesis.</p>
                    </div>
                @endif
            </div>

            <div class="shrink-0 flex justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
