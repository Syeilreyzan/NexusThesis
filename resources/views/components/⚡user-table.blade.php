<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    #[Computed]
    public function users()
    {
        return User::query()->with('roles')->latest()->paginate(10);
    }
};
?>

<div>
    <flux:heading size="lg" class="mb-4">
        List of Users
    </flux:heading>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Created</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong">
                        {{ $user->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $user->email }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @forelse ($user->roles as $role)
                            <flux:badge size="sm" class="mr-1">
                                {{ str($role->name)->title() }}
                            </flux:badge>
                        @empty
                            <flux:badge size="sm" color="zinc">
                                No role assigned
                            </flux:badge>
                        @endforelse
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $user->created_at?->format('M j, Y') }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center">
                        No users found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
