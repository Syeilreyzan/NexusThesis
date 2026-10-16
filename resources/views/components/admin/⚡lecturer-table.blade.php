<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Thesis;
use App\Helpers\Logger;
use Flux\Flux;
use Illuminate\Validation\Rule;

new class extends Component
{
    use WithPagination;

    public ?int $selectedLecturerId = null;
    public string $search = '';

    // Properties for editing
    public string $editName = '';
    public string $editEmail = '';

    // Properties for Thesis Assignment
    public array $supervisedThesisIds = [];
    public $selectedThesisToAdd = ''; // Bound to the dropdown

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function lecturers()
    {
        $query = User::role('lecturer')
            ->with(['supervisedTheses', 'examinedTheses'])
            ->select(['id', 'name', 'email', 'created_at'])
            ->latest();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(10);
    }

    #[Computed]
    public function selectedLecturer()
    {
        if (!$this->selectedLecturerId) {
            return null;
        }

        return User::with(['supervisedTheses', 'examinedTheses'])->find($this->selectedLecturerId);
    }

    // --- Computed Properties for Add/Remove UI ---

    #[Computed]
    public function allTheses()
    {
        // Fetch all theses to populate the dropdown
        return Thesis::select('id', 'title')->orderBy('created_at', 'desc')->get();
    }

    #[Computed]
    public function currentSupervisedThesesList()
    {
        // Fetch the theses that are currently in the temporary array state
        if (empty($this->supervisedThesisIds)) return collect();
        return Thesis::whereIn('id', $this->supervisedThesisIds)->get();
    }

    // --- Action Methods ---

    public function viewLecturer(int $id)
    {
        $this->selectedLecturerId = $id;
        Flux::modal('view-lecturer')->show();
    }

    public function editLecturer()
    {
        $lecturer = $this->selectedLecturer;

        if ($lecturer) {
            $this->editName = $lecturer->name;
            $this->editEmail = $lecturer->email;

            // Load their current supervised theses into the array
            $this->supervisedThesisIds = $lecturer->supervisedTheses->pluck('id')->toArray();
            $this->selectedThesisToAdd = '';

            Flux::modal('view-lecturer')->close();
            Flux::modal('edit-lecturer')->show();
        }
    }

    // --- Add / Remove Handlers ---

    public function addThesisToSupervisor()
    {
        if ($this->selectedThesisToAdd && !in_array((int)$this->selectedThesisToAdd, $this->supervisedThesisIds)) {
            $this->supervisedThesisIds[] = (int) $this->selectedThesisToAdd;
        }
        $this->selectedThesisToAdd = ''; // Reset dropdown after adding
    }

    public function removeThesisFromSupervisor($thesisId)
    {
        // Remove the ID from the array
        $this->supervisedThesisIds = array_values(array_diff($this->supervisedThesisIds, [$thesisId]));
    }

    // --- Final Save ---

    public function updateLecturer()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->selectedLecturerId)],
        ]);

        if ($this->selectedLecturerId) {
            $lecturer = User::findOrFail($this->selectedLecturerId);
            $oldName = $lecturer->name;

            // 1. Update Profile Details
            $lecturer->update([
                'name' => $validated['editName'],
                'email' => $validated['editEmail'],
            ]);

            // 2. Sync Supervisor Assignments in DB
            // Remove thesis supervision if they were taken off the list
            Thesis::where('supervisor_id', $lecturer->id)
                ->whereNotIn('id', $this->supervisedThesisIds)
                ->update(['supervisor_id' => null]);

            // Apply supervision to all theses currently in the list
            if (!empty($this->supervisedThesisIds)) {
                Thesis::whereIn('id', $this->supervisedThesisIds)->update(['supervisor_id' => $lecturer->id]);
            }

            // 3. Log it
            $adminName = auth()->user()->name;
            Logger::action(
                'lecturer_updated',
                "Admin {$adminName} updated profile and thesis supervisor assignments for {$oldName} (ID: {$lecturer->id})."
            );

            Flux::modal('edit-lecturer')->close();
            $this->selectedLecturerId = null;

            session()->flash('success', 'Lecturer updated successfully.');
        }
    }

    public function deleteLecturer()
    {
        if (auth()->user()->hasRole('admin') && $this->selectedLecturerId) {
            $lecturer = User::findOrFail($this->selectedLecturerId);
            $lecturerId = $lecturer->id;
            $lecturerName = $lecturer->name;

            $lecturer->delete();

            $adminName = auth()->user()->name;
            Logger::action(
                'lecturer_deleted',
                "Admin {$adminName} deleted lecturer {$lecturerName} (ID: {$lecturerId})."
            );

            $this->selectedLecturerId = null;
            session()->flash('success', 'Lecturer removed successfully.');
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
    <div class="mb-4 flex flex-col sm:flex-row gap-4 items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
        <div class="w-full max-w-sm">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search by name or email..."
                clearable
            />
        </div>
    </div>

    <flux:table :paginate="$this->lecturers">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Assigned Theses</flux:table.column>
            <flux:table.column>Joined Date</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->lecturers as $lecturer)
                <flux:table.row :key="$lecturer->id">

                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->lecturers->currentPage() - 1) * $this->lecturers->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $lecturer->name }}</div>
                        <div class="text-sm text-zinc-500">
                            <a href="mailto:{{ $lecturer->email }}" class="hover:underline">
                                {{ $lecturer->email }}
                            </a>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-1 flex-wrap">
                            @foreach($lecturer->getRoleNames() as $role)
                                <flux:badge color="blue" size="sm">{{ str($role)->title() }}</flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($lecturer->supervisedTheses->isNotEmpty() || $lecturer->examinedTheses->isNotEmpty())
                            <div class="flex flex-col gap-1">
                                @foreach($lecturer->supervisedTheses as $thesis)
                                    <div class="text-sm">
                                        <span class="text-zinc-500 text-xs uppercase tracking-wider font-semibold mr-1">Supervisor:</span>
                                        {{ $thesis->title }}
                                    </div>
                                @endforeach

                                @foreach($lecturer->examinedTheses as $thesis)
                                    <div class="text-sm">
                                        <span class="text-zinc-500 text-xs uppercase tracking-wider font-semibold mr-1">Examiner:</span>
                                        {{ $thesis->title }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-zinc-400 italic text-sm">No theses assigned</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $lecturer->created_at->format('M j, Y') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-2">
                            {{-- Unified View Button --}}
                            <flux:button size="sm" variant="primary" icon="eye"
                                wire:click="viewLecturer({{ $lecturer->id }})">
                                View
                            </flux:button>

                            @if (auth()->user()->hasRole('admin'))
                                <flux:button size="sm" variant="danger" icon="trash"
                                    wire:click="$set('selectedLecturerId', {{ $lecturer->id }})"
                                    x-on:click="$flux.modal('remove-lecturer').show()">
                                    Remove
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-6">
                        No lecturers found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- --- MODALS --- --}}

    {{-- 1. View Lecturer Modal --}}
    <flux:modal name="view-lecturer" class="md:w-[600px]">
        @if ($this->selectedLecturer)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Lecturer Details</flux:heading>
                    <flux:text class="mt-1">Detailed information about this lecturer.</flux:text>
                </div>

                <div>
                    <flux:heading size="sm">Name</flux:heading>
                    <p class="mt-1">{{ $this->selectedLecturer->name }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Email</flux:heading>
                    <p class="mt-1">{{ $this->selectedLecturer->email }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Joined Date</flux:heading>
                    <p class="mt-1">{{ $this->selectedLecturer->created_at->format('F j, Y') }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Roles</flux:heading>
                    <div class="mt-2 flex gap-1 flex-wrap">
                        @foreach($this->selectedLecturer->getRoleNames() as $role)
                            <flux:badge color="blue" size="sm">{{ str($role)->title() }}</flux:badge>
                        @endforeach
                    </div>
                </div>

                <div>
                    <flux:heading size="sm">Assigned Theses</flux:heading>
                    <div class="mt-2 space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 max-h-48 overflow-y-auto">
                        @if($this->selectedLecturer->supervisedTheses->isEmpty() && $this->selectedLecturer->examinedTheses->isEmpty())
                            <p class="text-sm text-zinc-500 italic">No theses assigned.</p>
                        @endif

                        @foreach($this->selectedLecturer->supervisedTheses as $thesis)
                            <div class="text-sm">
                                <span class="text-zinc-500 text-xs uppercase tracking-wider font-semibold mr-1">Supervisor:</span>
                                {{ $thesis->title }}
                            </div>
                        @endforeach

                        @foreach($this->selectedLecturer->examinedTheses as $thesis)
                            <div class="text-sm">
                                <span class="text-zinc-500 text-xs uppercase tracking-wider font-semibold mr-1">Examiner:</span>
                                {{ $thesis->title }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Close</flux:button>
                    </flux:modal.close>

                    @if (auth()->user()->hasRole('admin'))
                        {{-- The Edit Button is now located here --}}
                        <flux:button type="button" variant="filled" color="amber" icon="pencil" wire:click="editLecturer">
                            Edit Lecturer
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Edit Lecturer Modal --}}
    <flux:modal name="edit-lecturer" class="md:w-[600px]">
        <form wire:submit="updateLecturer" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Lecturer</flux:heading>
                <flux:text class="mt-1">Update profile details and supervisor assignments.</flux:text>
            </div>

            {{-- 1. Profile Details --}}
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="editName" placeholder="Lecturer's full name" />
                    <flux:error class="mt-0!" name="editName" />
                </flux:field>

                <flux:field>
                    <flux:label>Email Address</flux:label>
                    <flux:input type="email" wire:model="editEmail" placeholder="lecturer@example.com" />
                    <flux:error class="mt-0!" name="editEmail" />
                </flux:field>
            </div>

            <hr class="border-zinc-200 dark:border-zinc-700 my-4" />

            {{-- 2. Supervisor Assignment List --}}
            <div>
                <flux:heading size="sm" class="mb-2">Assigned as Supervisor</flux:heading>

                {{-- List of Currently Assigned --}}
                <div class="space-y-2 mb-4">
                    @forelse($this->currentSupervisedThesesList as $thesis)
                        <div class="flex items-center justify-between p-2 rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate pr-4">
                                {{ $thesis->title }}
                            </span>
                            {{-- type="button" prevents the form from saving early --}}
                            <flux:button size="sm" variant="danger" type="button" wire:click="removeThesisFromSupervisor({{ $thesis->id }})">
                                Remove
                            </flux:button>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 italic">This lecturer is not supervising any theses yet.</p>
                    @endforelse
                </div>

                {{-- Add New Assignment Dropdown --}}
                <div class="flex items-center gap-2">
                    <select wire:model="selectedThesisToAdd" class="w-full rounded-lg border-zinc-200 bg-white p-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-zinc-600 dark:focus:ring-zinc-600">
                        <option value="">Select a thesis to add...</option>
                        @foreach($this->allTheses as $thesis)
                            {{-- Hide theses they are already supervising from the dropdown --}}
                            @if(!in_array($thesis->id, $this->supervisedThesisIds))
                                <option value="{{ $thesis->id }}">{{ $thesis->title }}</option>
                            @endif
                        @endforeach
                    </select>

                    <flux:button type="button" wire:click="addThesisToSupervisor">
                        Add
                    </flux:button>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 mt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateLecturer">
                    Save Changes
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- 3. Delete Confirmation Modal --}}
    <flux:modal name="remove-lecturer" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Lecturer?</flux:heading>
                <flux:subheading>
                    Are you sure you want to remove this lecturer? This action cannot be undone.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="deleteLecturer" x-on:click="$flux.modal('remove-lecturer').close()">
                    Remove
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
