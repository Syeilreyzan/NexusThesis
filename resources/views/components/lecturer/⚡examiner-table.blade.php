<?php

use App\Models\Thesis;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Flux\Flux;
use App\Helpers\Logger; // Added Logger import

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $gradeFilter = '';

    public ?int $selectedThesisId = null;
    public ?string $previewDocumentUrl = null;

    // Grading properties
    public ?int $gradeScore = null;
    public string $gradeStatus = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedGradeFilter()
    {
        $this->resetPage();
    }

    public function updatedGradeScore($value)
    {
        if ($value === '' || $value === null) {
            $this->gradeStatus = '';
            return;
        }

        $score = (int) $value;

        if ($score >= 80) $this->gradeStatus = 'A (Excellent)';
        elseif ($score >= 75) $this->gradeStatus = 'A-';
        elseif ($score >= 70) $this->gradeStatus = 'B+';
        elseif ($score >= 65) $this->gradeStatus = 'B (Good)';
        elseif ($score >= 60) $this->gradeStatus = 'B-';
        elseif ($score >= 55) $this->gradeStatus = 'C+';
        elseif ($score >= 50) $this->gradeStatus = 'C (Pass)';
        elseif ($score >= 40) $this->gradeStatus = 'D (Fail)';
        else $this->gradeStatus = 'F (Fail)';
    }

    #[Computed]
    public function examinerTheses()
    {
        $query = Thesis::query()
            ->with(['student', 'document'])
            ->where('examiner_id', auth()->id())
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

        if (!empty($this->gradeFilter)) {
            $query->where('grade', 'like', '%(' . $this->gradeFilter . '%');
        }

        return $query->paginate(10);
    }

    #[Computed]
    public function selectedThesis()
    {
        if (!$this->selectedThesisId) {
            return null;
        }
        return Thesis::find($this->selectedThesisId);
    }

    public function previewThesis(int $thesisId)
    {
        $thesis = Thesis::with('document')->findOrFail($thesisId);

        $this->selectedThesisId = $thesis->id;

        if ($thesis->document) {
            $this->previewDocumentUrl = route('theses.document.view', ['thesis' => $thesis->slug ?? $thesis->id]);
        } else {
            $this->previewDocumentUrl = null;
        }

        Flux::modal('preview-examiner-thesis')->show();
    }

    public function openGradingModal()
    {
        Flux::modal('preview-examiner-thesis')->close();

        $this->gradeScore = null;
        $this->gradeStatus = '';

        Flux::modal('grading-modal')->show();
    }

    public function submitGrade()
    {
        $this->validate([
            'gradeScore' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($this->selectedThesisId) {
            $thesis = Thesis::findOrFail($this->selectedThesisId);

            if ($thesis->examiner_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }

            $formattedGrade = $this->gradeScore . '% (' . explode(' ', $this->gradeStatus)[0] . ')';

            $thesis->update([
                'grade' => $formattedGrade,
                'grade_status' => 'completed',
                'status' => 'completed'
            ]);

            // --- Added Logger for Grade Submission ---
            $examinerName = auth()->user()->name;
            Logger::action(
                'grade_submitted',
                "Examiner {$examinerName} assigned grade {$formattedGrade} to thesis '{$thesis->title}' (ID: {$thesis->id})."
            );
            // ----------------------------------------

            Flux::modal('grading-modal')->close();
            $this->selectedThesisId = null;
            $this->previewDocumentUrl = null;

            Flux::toast('Thesis graded successfully.');
        }
    }
};
?>

<div>
    <div class="mb-4 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
        <div class="w-full sm:w-72">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search title or student..."
                clearable
            />
        </div>

        <div class="w-full sm:w-36">
            <flux:select wire:model.live="gradeFilter" placeholder="All Grades">
                <flux:select.option value="">All Grades</flux:select.option>
                <flux:select.option value="A">Grade A</flux:select.option>
                <flux:select.option value="B">Grade B</flux:select.option>
                <flux:select.option value="C">Grade C</flux:select.option>
                <flux:select.option value="D">Grade D</flux:select.option>
                <flux:select.option value="F">Grade F</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->examinerTheses">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Thesis Title</flux:table.column>
            <flux:table.column>Student Details</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Grade</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->examinerTheses as $thesis)
                <flux:table.row :key="$thesis->id">
                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->examinerTheses->currentPage() - 1) * $this->examinerTheses->perPage() + $loop->iteration }}
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
                                'pending_approval', 'approved' => 'amber',
                                'completed' => 'green',
                                'rejected' => 'red',
                                default => 'zinc',
                            } }}"
                        >
                            {{ $thesis->status === 'approved' ? 'Result Awaited' : str($thesis->status)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($thesis->grade)
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $thesis->grade }}</span>
                        @else
                            <span class="text-zinc-400 italic text-sm">Not Graded</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button size="sm" variant="filled" color="amber" icon="document-magnifying-glass" wire:click="previewThesis({{ $thesis->id }})">
                            @if (!empty($thesis->grade))
                                Review
                            @else
                                Review & Grade
                            @endif
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-6">
                        No theses assigned as Examiner matching your search or filters.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- --- 1. PDF Preview Modal --- --}}
    <flux:modal name="preview-examiner-thesis" class="w-full max-w-5xl h-[85vh]">
        <div class="flex flex-col h-full space-y-4">
            <div class="shrink-0">
                <flux:heading size="lg">Examiner Review</flux:heading>
            </div>

            <div class="flex-1 min-h-0 bg-zinc-100 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden relative">
                <div wire:loading wire:target="previewThesis" class="absolute inset-0 flex items-center justify-center bg-zinc-100/80 dark:bg-zinc-800/80 z-10">
                    Loading document...
                </div>

                @if($previewDocumentUrl)
                    <iframe
                        src="{{ $previewDocumentUrl }}"
                        class="w-full h-full border-0"
                        title="Thesis Document Preview"
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
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>

                @if ($this->selectedThesis)
                    <div>
                        @if (!$this->selectedThesis->grade)
                            <flux:button variant="primary" icon="pencil-square" wire:click="openGradingModal">
                                Proceed to Grading
                            </flux:button>
                        @else
                            <flux:badge color="green" size="lg" icon="check-circle">
                                Graded: {{ $this->selectedThesis->grade }}
                            </flux:badge>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>

    {{-- --- 2. Input Grade Modal --- --}}
    <flux:modal name="grading-modal" class="md:w-[450px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Submit Grade</flux:heading>
                <flux:text class="mt-2">
                    Enter the final percentage score for this thesis (0-100).
                </flux:text>
            </div>

            <div>
                <flux:field>
                    <flux:label>Percentage Score</flux:label>
                    <flux:input type="number" min="0" max="100" wire:model.live="gradeScore" placeholder="e.g. 85" class="text-lg font-medium" />
                    <flux:error class="mt-0!" name="gradeScore" />
                </flux:field>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Status Grade:</span>
                @if($gradeStatus)
                    <flux:badge size="lg" color="{{ str_contains($gradeStatus, 'Fail') ? 'red' : 'green' }}">
                        {{ $gradeStatus }}
                    </flux:badge>
                @else
                    <span class="text-zinc-400 text-sm italic">Type a score above</span>
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" icon="check-badge" wire:click="submitGrade" wire:loading.attr="disabled" wire:target="submitGrade">
                    Confirm Grade
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
