<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;
use App\Helpers\Logger;
use Flux\Flux; // Added missing Flux import

new class extends Component {
    use WithPagination;

    // --- Search & Filter Properties ---
    public string $search = '';
    public string $roleFilter = '';
    public ?int $selectedUserId = null; // Fixed property type and initial value

    // --- Reset pagination when filters change ---
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    // --- Fetch available roles for the dropdown ---
    #[Computed]
    public function availableRoles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        $query = User::query()->with('roles')->latest();

        // 1. Apply Search Filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // 2. Apply Role Filter using Spatie's role scope
        if (!empty($this->roleFilter)) {
            $query->role($this->roleFilter);
        }

        return $query->paginate(10);
    }

    public function confirmDelete(int $id): void
    {
        $this->selectedUserId = $id;
        Flux::modal('remove-user')->show();
    }

    public function deleteUser(): void
    {
        if ($this->selectedUserId) {
            $user = User::findOrFail($this->selectedUserId);

            // Safeguard: Prevent the logged-in admin from deleting themselves
            if ($user->id === auth()->id()) {
                Flux::toast('You cannot delete your own account.', variant: 'danger');
                Flux::modal('remove-user')->close();
                $this->selectedUserId = null;
                return;
            }

            // This will automatically perform a Soft Delete because of the SoftDeletes trait
            $user->delete();

            // Defined $adminName before using it in the logger
            $adminName = auth()->user()->name;

            Logger::action(
                'admin_deleted_user',
                "Admin {$adminName} deleted user {$user->name} (ID: {$user->id})."
            );

            Flux::toast('User removed successfully.');

            Flux::modal('remove-user')->close();
            $this->selectedUserId = null;
        }
    }
};
?>

<div>
    {{-- --- Search and Filter Bar --- --}}
    <div class="mb-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="w-full max-w-sm">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search by name or email..."
                clearable
            />
        </div>

        <div class="w-full sm:w-64">
            <flux:select wire:model.live="roleFilter" >
                <flux:select.option value="">All Roles</flux:select.option>
                @foreach ($this->availableRoles as $role)
                    <flux:select.option value="{{ $role->name }}">
                        {{ str($role->name)->title() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>#</flux:table.column>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    {{-- Row Numbering Calculation --}}
                    <flux:table.cell class="text-zinc-500">
                        {{ ($this->users->currentPage() - 1) * $this->users->perPage() + $loop->iteration }}
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        {{ $user->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <a href="mailto:{{ $user->email }}" class="text-blue-500 hover:underline">
                            {{ $user->email }}
                        </a>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-1 flex-wrap">
                            @forelse ($user->roles as $role)
                                <flux:badge
                                    size="sm"
                                    color="{{ match($role->name) {
                                        'admin' => 'red',
                                        'lecturer' => 'amber',
                                        'student' => 'green',
                                        default => 'zinc',
                                    } }}"
                                >
                                    {{ str($role->name)->title() }}
                                </flux:badge>
                            @empty
                                <flux:badge size="sm" color="zinc">
                                    No role assigned
                                </flux:badge>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $user->created_at?->format('M j, Y') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->id !== auth()->id()) {{-- Hide delete button for the current user --}}
                            <flux:button size="sm" variant="danger" icon="trash"
                                wire:click="confirmDelete({{ $user->id }})">
                                Remove
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    {{-- Updated colspan to 6 to cover all columns --}}
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-6">
                        No users found matching your search.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Remove user modal --}}
    <flux:modal name="remove-user" class="md:w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove User</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to remove this user? Their data will be archived securely (soft deleted).
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteUser" wire:loading.attr="disabled" wire:target="deleteUser">
                    Confirm Removal
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
