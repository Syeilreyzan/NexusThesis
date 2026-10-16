<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\User;

new class extends Component
{
    use WithPagination;

    public ?int $selectedLecturerId = null;
    public string $search = '';

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

        // 3. Apply the search filter if the user typed something
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

        return User::find($this->selectedLecturerId);
    }

    // --- Action Methods ---

    public function viewLecturer($id)
    {
        // Example: Redirect to a lecturer profile page
        // return redirect()->route('lecturers.show', $id);
    }

    public function editLecturer($id)
    {
        // Example: Redirect to edit page
        // return redirect()->route('lecturers.edit', $id);
    }

    public function deleteLecturer()
    {
        // Make sure only admins can delete users
        if (auth()->user()->hasRole('admin') && $this->selectedLecturerId) {
            $lecturer = User::findOrFail($this->selectedLecturerId);
            $lecturer->delete();

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
            {{-- .live.debounce.300ms prevents spamming the database while typing --}}
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
            <flux:table.column>Assigned Theses</flux:table.column> <!-- New Column -->
            <flux:table.column>Joined Date</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->lecturers as $lecturer)
                <flux:table.row :key="$lecturer->id">

                    {{-- Row Numbering Calculation --}}
                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->lecturers->currentPage() - 1) * $this->lecturers->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    {{-- Name & Email --}}
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $lecturer->name }}</div>
                        <div class="text-sm text-zinc-500">
                            <a href="mailto:{{ $lecturer->email }}" class="hover:underline">
                                {{ $lecturer->email }}
                            </a>
                        </div>
                    </flux:table.cell>

                    {{-- Dynamic Spatie Roles --}}
                    <flux:table.cell>
                        <div class="flex gap-1 flex-wrap">
                            @foreach($lecturer->getRoleNames() as $role)
                                <flux:badge color="blue" size="sm">{{ str($role)->title() }}</flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>

                    {{-- Thesis Names (Supervisor & Examiner) --}}
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

                    {{-- Joined Date --}}
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $lecturer->created_at->format('M j, Y') }}
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" icon="eye"
                                wire:click="viewLecturer({{ $lecturer->id }})">
                                View
                            </flux:button>

                            @if (auth()->user()->hasRole('admin'))
                                <flux:button size="sm" variant="filled" color="amber" icon="pencil"
                                    wire:click="editLecturer({{ $lecturer->id }})">
                                    Edit
                                </flux:button>

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
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-6">
                        No lecturers found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{--
        Placeholder for the Flux Modal to confirm deletion
        Ensure you have this defined in your view if you are using the delete button!
    --}}
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
