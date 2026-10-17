<?php

use App\Models\Thesis;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Flux\Flux;
use App\Helpers\Logger;
use Livewire\Attributes\Locked;


new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $previewDocumentUrl = null;

    #[Locked]
    public ?int $selectedThesisId = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openUpdateModal(int $thesisId)
    {
        $this->selectedThesisId = $thesisId;
        Flux::modal('update-thesis-status')->show();
    }

    #[Computed]
    public function svTheses()
    {
        $query = Thesis::query()
            ->with(['student', 'document'])
            ->where('supervisor_id', auth()->id())
            ->where('status', '!=', 'draft')
            ->latest();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhereHas('student', function ($subQuery) {
                        $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->paginate(10);
    }

    // --- Computed Property for Selected Thesis ---
    #[Computed]
    public function selectedThesis()
    {
        if (!$this->selectedThesisId) {
            return null;
        }

       $thesis =  Thesis::with(['student', 'document'])->find($this->selectedThesisId);

        if ($thesis && $thesis->supervisor_id !== auth()->id()) {   // examiner_id for the examiner one
            abort(403);
        }

        return $thesis;
    }

    // Point back to your ThesisDocumentController to view the uploaded file
    public function previewThesis(int $thesisId)
    {
        $thesis = Thesis::with('document')->findOrFail($thesisId);

        if ($thesis->supervisor_id !== auth()->id()) {   // or examiner_id
                abort(403, 'Unauthorized action.');
            }

        $this->selectedThesisId = $thesis->id;

        if ($thesis->document) {
            $this->previewDocumentUrl = route('theses.document.view', ['thesis' => $thesis->slug ?? $thesis->id]);
        } else {
            $this->previewDocumentUrl = null;
        }

        Flux::modal('preview-thesis')->show();
    }

    public function updateStatus(string $status)
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            return;
        }

        if ($this->selectedThesisId) {
            $thesis = Thesis::findOrFail($this->selectedThesisId);

            if ($thesis->supervisor_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }

            $thesis->update(['status' => $status]);

            // --- Added Logger ---
            $supervisorName = auth()->user()->name;
            $actionTag = $status === 'approved' ? 'thesis_approved' : 'thesis_rejected';
            $statusText = $status === 'approved' ? 'approved' : 'rejected';

            Logger::action(
                $actionTag,
                "Supervisor {$supervisorName} {$statusText} thesis '{$thesis->title}' (ID: {$thesis->id})."
            );
            // --------------------

            Flux::modal('preview-thesis')->close();
            Flux::modal('update-thesis-status')->close();

            $this->selectedThesisId = null;
            $this->previewDocumentUrl = null;

            $toastText = $status === 'approved' ? 'Approved' : 'Rejected';
            Flux::toast("Thesis status has been {$toastText}.");
        }
    }
};
?>

<div>
    {{-- Header & Search Bar --}}
    <div class="mb-4 flex flex-col sm:flex-row gap-4 items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
        <div class="w-full sm:w-72">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search title or student..."
                clearable
            />
        </div>
    </div>

    {{-- The Table --}}
    <flux:table :paginate="$this->svTheses">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Thesis Title</flux:table.column>
            <flux:table.column>Student Details</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->svTheses as $thesis)
                <flux:table.row :key="$thesis->id">
                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->svTheses->currentPage() - 1) * $this->svTheses->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        {{ $thesis->title }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">
                            {{ $thesis->student->name ?? 'N/A' }}
                        </div>
                        <div class="text-sm text-zinc-500">
                            @if($thesis->student?->email)
                                <a href="mailto:{{ $thesis->student->email }}" class="hover:underline">
                                    {{ $thesis->student->email }}
                                </a>
                            @else
                                N/A
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge
                            size="sm"
                            color="{{ match($thesis->status) {
                                'draft' => 'zinc',
                                'pending_approval' => 'amber',
                                'approved' => 'green',
                                'rejected' => 'red',
                                default => 'zinc',
                            } }}"
                        >
                            {{ str($thesis->status)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="primary" icon="eye" wire:click="previewThesis({{ $thesis->id }})">
                                View Document
                            </flux:button>

                            @if($thesis->status === 'pending_approval')
                                <flux:button size="sm" variant="filled" color="amber" icon="clipboard-document-check" wire:click="openUpdateModal({{ $thesis->id }})">
                                    Update
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-6">
                        No theses assigned as Supervisor matching your search.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- --- Update Status Modal --- --}}
    <flux:modal name="update-thesis-status" class="md:w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Update Thesis Status</flux:heading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" icon="x-mark" wire:click="updateStatus('rejected')" wire:loading.attr="disabled" wire:target="updateStatus">
                    Reject
                </flux:button>

                <flux:button variant="primary" icon="check" wire:click="updateStatus('approved')" wire:loading.attr="disabled" wire:target="updateStatus">
                    Approve
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- --- Uploaded PDF Preview Modal --- --}}
    <flux:modal name="preview-thesis" class="w-full max-w-5xl h-[85vh]">
        <div class="flex flex-col h-full space-y-4">
            <div class="shrink-0">
                <flux:heading size="lg">Review Thesis Document</flux:heading>
                <flux:text class="mt-1">
                    Please review the uploaded document carefully before making your final decision.
                </flux:text>
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

            <div class="shrink-0 flex justify-between items-center pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                @if($this->selectedThesis && $this->selectedThesis->status === 'pending_approval')
                    <div class="flex gap-3">
                        <flux:button variant="danger" icon="x-mark" wire:click="updateStatus('rejected')" wire:loading.attr="disabled" wire:target="updateStatus">
                            Reject
                        </flux:button>

                        <flux:button variant="primary" icon="check" wire:click="updateStatus('approved')" wire:loading.attr="disabled" wire:target="updateStatus">
                            Approve
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
